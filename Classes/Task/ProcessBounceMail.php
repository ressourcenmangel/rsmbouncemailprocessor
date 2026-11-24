<?php
declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Task;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use Undkonsorten\CuteMailing\Domain\Repository\NewsletterRepository;


/**
 * Class ProcessBounceMail
 * @package RSM\Rsmbouncemailprocessor\Scheduler
 * @author Ralph Brugger <ralph.brugger@ressourcenmangel.de>
 */
class ProcessBounceMail extends AbstractTask
{
    use LazyServicesTrait;


    public $conf;
    /**
     * newsletterRepository object
     * @var NewsletterRepository
     */
    protected NewsletterRepository $newsletterRepository;



    /**
     * initializes the class
     *
     */
    public function initClass(): void
    {

        // TS Setup
        $this->conf = $this->getModuleTs('tx_rsmbouncemailprocessor');

        /** @var NewsletterRepository $newsletterRepository */
        $this->newsletterRepository = GeneralUtility::makeInstance(NewsletterRepository::class);

    }


    /**
     * execute the scheduler task.
     *
     * @return bool
     */
    public function execute(): bool
    {

        // defaults
        $result = false;
        $newsletter = null;
        $recipientLists = [];

        // init
        $this->initClass();

        // first, query all newsletters grouped by recipient_list to get the recipient lists
        $queryBuilderReadNewsletter = $this->connectionPool()->getQueryBuilderForTable('tx_cutemailing_domain_model_newsletter');
        $queryBuilderReadNewsletter = $queryBuilderReadNewsletter
            ->select('uid', 'recipient_list')
            ->from('tx_cutemailing_domain_model_newsletter')
            ->where(
                $queryBuilderReadNewsletter->expr()->eq('hidden', 0),
                $queryBuilderReadNewsletter->expr()->eq('deleted', 0),
            )
            ->groupBy('recipient_list')
            ->executeQuery();

        // save the recipient lists
        while ($row = $queryBuilderReadNewsletter->fetchAssociative()) {
            if ($row['uid']) {
                $newsletter = $this->newsletterRepository->findByUid($row['uid']);
                if ($newsletter) {
                    // get the newsletters recipient list, cute_mailing throws if it cannot be loaded
                    try {
                        $recipientList = $newsletter->getRecipientList();
                    } catch (\TypeError) {
                        $recipientList = null;
                        $this->logger?->warning('Recipient list of newsletter could not be loaded, skipped', ['newsletter' => $row['uid']]);
                    }
                    if ($recipientList) {
                        $recipientLists[$recipientList->getUid()] = $recipientList;
                    }
                }
            }
        }
//\TYPO3\CMS\Core\Utility\DebugUtility::debug($recipientLists, '$recipientLists');

        // Walk through the recipientreport and get those rcords that reached their limits
        $connection = $this->connectionPool()->getConnectionForTable('tx_rsmbouncemailprocessor_domain_model_recipientreport');

        // Circuit breaker: a blacklisting or a misconfiguration can push many recipients over their
        // limits at once. Remove nobody in that case, the error is shown in the scheduler module.
        $maxRemovalsPerRun = (int)($this->conf['settings.']['maxRemovalsPerRun'] ?? 0);
        if ($maxRemovalsPerRun > 0) {
            $candidates = $this->countRemovalCandidates();
            if ($candidates > $maxRemovalsPerRun) {
                $message = sprintf(
                    '%d recipients would be removed, more than maxRemovalsPerRun = %d. Nothing was removed. '
                    . 'Check the cause first (blacklisting of the sending server, wrong configuration, bounce reasons in the recipient reports). '
                    . 'Then either fix it or raise settings.maxRemovalsPerRun (0 disables the check) to continue.',
                    $candidates,
                    $maxRemovalsPerRun
                );
                $this->logger?->error($message, ['candidates' => $candidates, 'maxRemovalsPerRun' => $maxRemovalsPerRun]);
                throw new \RuntimeException($message, 1763900001);
            }
        }

        // Walk through all the delete limits
        if (isset($this->conf['settings.']['deletelimits.'])) {
            foreach ($this->conf['settings.']['deletelimits.'] as $key => $limit) {

                // checkt if valid
                if ($key !== '' && $limit > 0) {

                    // query the affected records
                    $queryBuilderReadRecipientreport = $this->connectionPool()->getQueryBuilderForTable('tx_rsmbouncemailprocessor_domain_model_recipientreport');
                    $resultReadRecipientreport = $queryBuilderReadRecipientreport
                        ->select('*')
                        ->from('tx_rsmbouncemailprocessor_domain_model_recipientreport')
                        ->where(
                            $queryBuilderReadRecipientreport->expr()->gte($key,
                                $queryBuilderReadRecipientreport->createNamedParameter($limit, Connection::PARAM_INT)),
                            // already removed, nothing to do until the address bounces again
                            $queryBuilderReadRecipientreport->expr()->eq('removed', 0),
                        )
                        ->executeQuery();

                    // Alle Records durchlaufen
                    while ($row = $resultReadRecipientreport->fetchAssociative()) {

                        // the email
                        $logvalue = $row[$key];

                        // remove the recipient from all recipient lists we've found
                        $removed = false;
                        foreach ($recipientLists as $recipientList) {
                            if ($recipientList && $row['email']) {

                                // remove the recipient, the count tells whether the address was in this list
                                try {
                                    $countBefore = $recipientList->getRecipientsCount();
                                    $recipientList->removeRecipientByEmail($row['email']);
                                    $this->persistenceManager()->persistAll();
                                    $removed = true;
                                    $removedFromList = $recipientList->getRecipientsCount() < $countBefore;
                                } catch (\Exception $exception) {
                                    $removedFromList = false;
                                    $this->logger?->warning('Recipient could not be removed from the recipient list', [
                                        'recipientreport' => (int)$row['uid'],
                                        'type' => $recipientList::class,
                                        'exception' => $exception::class,
                                        'code' => $exception->getCode(),
                                    ]);
                                }

                                // one delete log entry per list the address has been removed from
                                if ($removedFromList && (int)($this->conf['settings.']['deletelog.']['enabled'] ?? 0) === 1) {
                                    $deletelogPid = (int)($this->conf['settings.']['deletelog.']['pid'] ?? 0);
                                    if ($deletelogPid > 0) {
                                        $queryBuilderAddLog = $this->connectionPool()->getQueryBuilderForTable('tx_rsmbouncemailprocessor_domain_model_deletelog');
                                        $queryBuilderAddLog
                                            ->insert('tx_rsmbouncemailprocessor_domain_model_deletelog')
                                            ->values([
                                                'pid' => $deletelogPid,
                                                'email' => $row['email'],
                                                'origpid' => (int)($recipientList->getRecipientListPage() ?? 0),
                                                'tstamp' => time(),
                                                'crdate' => time(),
                                                'deletetime' => time(),
                                                'reasontext' => $key,
                                                'reasonvalue' => $logvalue
                                            ])
                                            ->executeStatement();
                                    }
                                }
                            }
                        }

                        // Make persistent
                        $this->persistenceManager()->persistAll();

                        // mark the report, so it is shown as removed and not processed again
                        if ($removed) {
                            $connection->update(
                                'tx_rsmbouncemailprocessor_domain_model_recipientreport',
                                ['removed' => time(), 'tstamp' => time()],
                                ['uid' => (int)$row['uid']]
                            );
                        }

                    }
                }

            }
        }

        return true;
    }


    /**
     * number of distinct recipients that reached any delete limit and are not removed yet
     */
    private function countRemovalCandidates(): int
    {
        $emails = [];
        foreach ($this->conf['settings.']['deletelimits.'] ?? [] as $key => $limit) {
            if ($key === '' || $limit <= 0) {
                continue;
            }
            $queryBuilder = $this->connectionPool()->getQueryBuilderForTable('tx_rsmbouncemailprocessor_domain_model_recipientreport');
            $result = $queryBuilder
                ->select('email')
                ->from('tx_rsmbouncemailprocessor_domain_model_recipientreport')
                ->where(
                    $queryBuilder->expr()->gte($key, $queryBuilder->createNamedParameter($limit, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('removed', 0),
                )
                ->executeQuery();
            while ($email = $result->fetchOne()) {
                $emails[strtolower((string)$email)] = true;
            }
        }
        return count($emails);
    }

    /**
     * returns the TS settings for a specific path
     * @param string $path the path
     * @return array
     */
    private
    function getModuleTs(
        $path
    ): array {
        $mysettings = [];

        $configurationManager = $this->configurationManager();
        $settings = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT,
            'rsmbouncemailprocessor');

        if (isset($settings['module.']["$path."])) {
            $mysettings = $settings['module.']["$path."];
        }
        return $mysettings;
    }
}
