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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Core\Database\ConnectionPool;
use RSM\Rsmbouncemailprocessor\Utility\Mailserver;
use RSM\Rsmbouncemailprocessor\Utility\Mailmessage;
use Undkonsorten\CuteMailing\Domain\Repository\SendOutRepository;
use Undkonsorten\CuteMailing\Domain\Repository\NewsletterRepository;
use Undkonsorten\Taskqueue\Domain\Model\TaskInterface;

/**
 * Class ProcessBounceMail
 * @package RSM\Rsmbouncemailprocessor\Scheduler
 * @author Ralph Brugger <ralph.brugger@ressourcenmangel.de>
 */
class CleanTaskQueue extends AbstractTask
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
        $deletesuceededafterdays = 0;

        // init
        $this->initClass();

        /*
         * deletetables :: tx_taskqueue_domain_model_task
         */
        // check config
        if (isset($this->conf['settings.']['deletetables.']['tx_taskqueue_domain_model_task.']['deleteafterdays'])){
            $deletesuceededafterdays = $this->conf['settings.']['deletetables.']['tx_taskqueue_domain_model_task.']['deleteafterdays'];
        }
        if($deletesuceededafterdays > 0){
            $datelimit = time() - ($deletesuceededafterdays * 86400);

            // delete from tx_taskqueue_domain_model_task
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_taskqueue_domain_model_task');
            $queryBuilder->getRestrictions()->removeAll();
            $affectedRows = $queryBuilder
                ->delete('tx_taskqueue_domain_model_task')
                ->where(
                    $queryBuilder->expr()->eq('status', TaskInterface::FINISHED),
                    $queryBuilder->expr()->lt('crdate', $datelimit)
                )
                ->executeStatement();
        }


        /*
         * deletetables :: tx_rsmbouncemailprocessor_domain_model_bouncereport
         */
        // check config
        if (isset($this->conf['settings.']['deletetables.']['tx_rsmbouncemailprocessor_domain_model_bouncereport.']['deleteafterdays'])){
            $deletesuceededafterdays = $this->conf['settings.']['deletetables.']['tx_rsmbouncemailprocessor_domain_model_bouncereport.']['deleteafterdays'];
        }
        if($deletesuceededafterdays > 0){
            $datelimit = time() - ($deletesuceededafterdays * 86400);

            // delete from tx_taskqueue_domain_model_task
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_rsmbouncemailprocessor_domain_model_bouncereport');
            $queryBuilder->getRestrictions()->removeAll();
            $affectedRows = $queryBuilder
                ->delete('tx_rsmbouncemailprocessor_domain_model_bouncereport')
                ->where(
                    $queryBuilder->expr()->lt('crdate', $datelimit)
                )
                ->executeStatement();
        }


        /*
         * deletetables :: tx_rsmbouncemailprocessor_domain_model_deletelog
         */
        // check config
        if (isset($this->conf['settings.']['deletetables.']['tx_rsmbouncemailprocessor_domain_model_deletelog.']['deleteafterdays'])){
            $deletesuceededafterdays = $this->conf['settings.']['deletetables.']['tx_rsmbouncemailprocessor_domain_model_deletelog.']['deleteafterdays'];
        }
        if($deletesuceededafterdays > 0){
            $datelimit = time() - ($deletesuceededafterdays * 86400);

            // delete from tx_taskqueue_domain_model_task
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_rsmbouncemailprocessor_domain_model_deletelog');
            $queryBuilder->getRestrictions()->removeAll();
            $affectedRows = $queryBuilder
                ->delete('tx_rsmbouncemailprocessor_domain_model_deletelog')
                ->where(
                    $queryBuilder->expr()->lt('deletetime', $datelimit)
                )
                ->executeStatement();
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

        $configurationManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::class);
        $settings = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT, 'rsmbouncemailprocessor');

        if (isset($settings['module.']["$path."])) {
            $mysettings = $settings['module.']["$path."];
        }
        return $mysettings;
    }

}
