<?php
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
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use RSM\Rsmbouncemailprocessor\Utility\Mailserver;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Class AnalyzeBounceMailAdditionalFields
 * This provides additional fields for the AnalyzeBounceMail task.
 *
 * @package RSM\Rsmbouncemailprocessor\Task
 * @author Ralph brugger <ralph.brugger@ressourcenmangel.de>
 */
class AnalyzeBounceMailAdditionalFields extends AbstractAdditionalFieldProvider
{
    private const LANGUAGE_FILE = 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_mod.xlf:';

    /**
     * This method is used to define new fields for adding or editing a task
     * In this case, it adds an email field
     *
     * @param array $taskInfo reference to the array containing the info used in the add/edit form
     * @param AnalyzeBounceMail $task when editing, reference to the current task object. Null when adding.
     * @param SchedulerModuleController $schedulerModule reference to the calling object (Scheduler's BE module)
     *
     * @return array Array containg all the information pertaining to the additional fields
     *               The array is multidimensional, keyed to the task class name and each field's id
     *               For each field it provides an associative sub-array with the following:
     *               ['code'] => The HTML code for the field
     *               ['label'] => The label of the field (possibly localized)
     *               ['cshKey'] => The CSH key for the field
     *               ['cshLabel'] => The code of the CSH label
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $serverHTML = '<input type="text" name="tx_scheduler[bounceServer]" value="' . ($task ? $task->getServer() : '') . '"/>';
        $portHTML = '<input type="text" name="tx_scheduler[bouncePort]" value="' . ($task ? $task->getPort() : '') . '"/>';
        $userHTML = '<input type="text" name="tx_scheduler[bounceUser]" value="' . ($task ? $task->getUser() : '') . '"/>';
        $passwordHTML = '<input type="password" name="tx_scheduler[bouncePassword]" value="' . ($task ? $task->getPassword() : '') . '"/>';
        $maxProcessedHTML = '<input type="text" name="tx_scheduler[bounceProcessed]" value="' . ($task ? $task->getMaxProcessed() : '') . '"/>';

        if($task){
            $serviceHTML = '<select name="tx_scheduler[bounceService]" id="bounceService">' .
                '<option value="imap" ' . ($task->getService() === 'imap'? 'selected="selected"' : '') . '>IMAP</option>' .
                '<option value="pop3" ' . ($task->getService() === 'pop3'? 'selected="selected"' : '') . '>POP3</option>' .
                '</select>';
            if($task->getDeletealways()){
                $deletealwaysHTML = '<input type="checkbox" name="tx_scheduler[bounceDeletealways]" value="1" checked="checked"/>';

            }else{
                $deletealwaysHTML = '<input type="checkbox" name="tx_scheduler[bounceDeletealways]" value="1"/>';
            }

        } else {
            $serviceHTML = '<select name="tx_scheduler[bounceService]" id="bounceService">' .
                '<option value="imap" >IMAP</option>' .
                '<option value="pop3" >POP3</option>' .
                '</select>';
            $deletealwaysHTML = '<input type="checkbox" name="tx_scheduler[bounceDeletealways]" value="1"/>';
        }

        $additionalFields = [];
        $additionalFields['server'] = $this->createAdditionalFields('server', $serverHTML);
        $additionalFields['port'] = $this->createAdditionalFields('port', $portHTML);
        $additionalFields['user'] = $this->createAdditionalFields('user', $userHTML);
        $additionalFields['password'] = $this->createAdditionalFields('password', $passwordHTML);
        $additionalFields['service'] = $this->createAdditionalFields('service', $serviceHTML);
        $additionalFields['maxProcessed'] = $this->createAdditionalFields('maxProcessed', $maxProcessedHTML);
        $additionalFields['deletealways'] = $this->createAdditionalFields('deletealways', $deletealwaysHTML);

        return $additionalFields;
    }

    /**
     * Takes care of saving the additional fields' values in the task's object
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param AnalyzeBounceMail $task Reference to the scheduler backend module
     * @return void
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task): void
    {
        $task->setServer($submittedData['bounceServer']);
        $task->setPort((int)$submittedData['bouncePort']);
        $task->setUser($submittedData['bounceUser']);
        $task->setPassword($submittedData['bouncePassword']);
        $task->setService($submittedData['bounceService']);
        $task->setMaxProcessed((int)$submittedData['bounceProcessed']);
        $task->setDeletealways((bool)($submittedData['bounceDeletealways'] ?? false));
    }

    /**
     * Validates the additional fields' values
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        // check if PHP IMAP is installed
        if (!extension_loaded('imap')) {
            $this->addMessage(
                $this->translate('scheduler.rsmbouncemail.phpImapError'),
                ContextualFeedbackSeverity::ERROR
            );
            return false;
        }

        // try connect to mail server
        /** @var Mailserver $mailServer */
        $mailServer = GeneralUtility::makeInstance(Mailserver::class);
        $connected = $mailServer->connect(
            (string)($submittedData['bounceServer'] ?? ''),
            $submittedData['bounceUser'] ?? '',
            $submittedData['bouncePassword'] ?? '',
            (int)($submittedData['bouncePort'] ?? 143),
            (string)($submittedData['bounceService'] ?? 'imap')
        );

        if ($connected === null) {
            $this->addMessage(
                $this->translate('scheduler.rsmbouncemail.dataVerification'),
                ContextualFeedbackSeverity::ERROR
            );
            return false;
        }

        return true;
    }

    protected function createAdditionalFields($fieldName, $fieldHTML)
    {
        // create server input field
        return [
            'code'     => $fieldHTML,
            'label'    => $this->translate('scheduler.rsmbouncemail.' . $fieldName),
            'cshKey'   => $fieldName,
            'cshLabel' => $this->translate('scheduler.rsmbouncemailcsh.' . $fieldName)
        ];
    }

    /**
     * Translate a label of the module language file (works with TYPO3 13 and 14)
     */
    protected function translate(string $key): string
    {
        $languageService = $GLOBALS['LANG'] ?? null;
        if (!$languageService instanceof LanguageService) {
            return $key;
        }
        return $languageService->sL(self::LANGUAGE_FILE . $key) ?: $key;
    }
}
