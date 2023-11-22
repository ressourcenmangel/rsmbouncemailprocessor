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
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Core\Database\ConnectionPool;
use RSM\Rsmbouncemailprocessor\Utility\Mailserver;
use RSM\Rsmbouncemailprocessor\Utility\Mailmessage;
use Undkonsorten\CuteMailing\Domain\Repository\SendOutRepository;
use Undkonsorten\CuteMailing\Domain\Repository\NewsletterRepository;

/**
 * Class ProcessBounceMail
 * @package RSM\Rsmbouncemailprocessor\Scheduler
 * @author Ralph Brugger <ralph.brugger@ressourcenmangel.de>
 */
class ProcessBounceMail extends AbstractTask
{


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

        // init
        $this->initClass();

        // DB connection fpr the table
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_rsmbouncemailprocessor_domain_model_recipientreport');

        // Walk through all the delete limits
        if (isset($this->conf['settings.']['deletelimits.'])) {
            foreach ($this->conf['settings.']['deletelimits.'] as $key => $limit) {

                // checkt if valid
                if ($key !== '' && $limit > 0) {

                    // query the affected records
                    $queryBuilderReadRecipientreport = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_rsmbouncemailprocessor_domain_model_recipientreport');
                    $resultReadRecipientreport = $queryBuilderReadRecipientreport
                        ->select('*')
                        ->from('tx_rsmbouncemailprocessor_domain_model_recipientreport')->where($queryBuilderReadRecipientreport->expr()->gte($key,
                        $queryBuilderReadRecipientreport->createNamedParameter($limit, Connection::PARAM_INT)))->executeQuery();

                    // Alle Records durchlaufen
                    while ($row = $resultReadRecipientreport->fetch()) {

                        // the email
                        $logrecipientreportuid = $row['uid'];
                        $logemail = $row['email'];
                        $logvalue = $row[$key];

                        // read the recipient in tt_address
                        $queryBuilderReadTTAddress = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_address');
                        $resultReadTTAddress = $queryBuilderReadTTAddress
                            ->select('uid', 'pid', 'email', 'tstamp', 'crdate')
                            ->from('tt_address')->where($queryBuilderReadTTAddress->expr()->eq('email',
                            $queryBuilderReadTTAddress->createNamedParameter($logemail, Connection::PARAM_STR)))->executeQuery();

                        if ($resultReadTTAddress) {
                            $rowReadTTAddress = $resultReadTTAddress->fetch();
                            if ($rowReadTTAddress) {
                                $logpid = $rowReadTTAddress['pid'];
                                $loguid = $rowReadTTAddress['uid'];
                                $tstamp = $rowReadTTAddress['tstamp'];
                                $crdate = $rowReadTTAddress['crdate'];

                                if ($logpid && $loguid) {

                                    // delete from tt_address
                                    $queryBuilderDeleteTTAddress = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_address');
                                    $affectedRows = $queryBuilderDeleteTTAddress
                                        ->delete('tt_address')
                                        ->where(
                                            $queryBuilderDeleteTTAddress->expr()->eq('uid',
                                                $queryBuilderDeleteTTAddress->createNamedParameter($loguid,
                                                    Connection::PARAM_INT))
                                        )
                                        ->executeStatement();

                                    // delete the recipientreport entry
                                    $queryBuilderDeletedRecipientreport = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_rsmbouncemailprocessor_domain_model_recipientreport');
                                    $resultDeleteRecipientreport = $queryBuilderDeletedRecipientreport
                                        ->delete('tx_rsmbouncemailprocessor_domain_model_recipientreport')
                                        ->where(
                                            $queryBuilderDeletedRecipientreport->expr()->eq('uid',
                                                $queryBuilderDeletedRecipientreport->createNamedParameter($logrecipientreportuid,Connection::PARAM_INT))
                                        )
                                        ->executeStatement();

                                    // delete log enabled?
                                    if (isset($this->conf['settings.']['deletelog.']['enabled']) && isset($this->conf['settings.']['deletelog.']['pid'])) {
                                        if ($this->conf['settings.']['deletelog.']['enabled'] == 1) {
                                            if ($this->conf['settings.']['deletelog.']['pid'] > 0) {

                                                // write delete log entry
                                                $queryBuilderAddLog = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_rsmbouncemailprocessor_domain_model_deletelog');
                                                $affectedRows = $queryBuilderAddLog
                                                    ->insert('tx_rsmbouncemailprocessor_domain_model_deletelog')
                                                    ->values([
                                                        'pid' => $this->conf['settings.']['deletelog.']['pid'],
                                                        'origpid' => $logpid,
                                                        'origuid' => $loguid,
                                                        'email' => $logemail,
                                                        'tstamp' => $tstamp,
                                                        'crdate' => $crdate,
                                                        'deletetime' => time(),
                                                        'reasontext' => $key,
                                                        'reasonvalue' => $logvalue
                                                    ])
                                                    ->executeStatement();
                                            }

                                        }
                                    }
                                }
                            }
                        }

                    }

                }
            }

        }

        return true;
    }



    /**
     * returns the TS settings for a specific path
     * @param string $path the path
     * @return array
     */
    private function getModuleTs($path): array
    {
        $mysettings = [];

        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $settings = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT, 'rsmbouncemailprocessor');

        if (isset($settings['module.']["$path."])) {
            $mysettings = $settings['module.']["$path."];
        }
        return $mysettings;
    }

}
