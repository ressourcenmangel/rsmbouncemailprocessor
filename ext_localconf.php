<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use RSM\Rsmbouncemailprocessor\Task\AnalyzeBounceMail;
use RSM\Rsmbouncemailprocessor\Task\AnalyzeBounceMailAdditionalFields;
use RSM\Rsmbouncemailprocessor\Task\ProcessBounceMail;
use RSM\Rsmbouncemailprocessor\Task\CleanTaskQueue;

if (!defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(function () {
    ExtensionManagementUtility::addTypoScript(
        'rsmbouncemailprocessor',
        'constants',
        "@import 'EXT:rsmbouncemailprocessor/Configuration/TypoScript/constants.typoscript'"
    );

    ExtensionManagementUtility::addTypoScript(
        'rsmbouncemailprocessor',
        'setup',
        "@import 'EXT:rsmbouncemailprocessor/Configuration/TypoScript/setup.typoscript'"
    );

    // bounce mail analyse scheduler
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][AnalyzeBounceMail::class] = [
        'extension' => 'rsmbouncemailprocessor',
        'title' => 'RSM analyze bounce mail',
        'description' => 'This task will get bounce mail from the configured mailbox',
        'additionalFields' => AnalyzeBounceMailAdditionalFields::class
    ];

    // bounce mail process scheduler
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][ProcessBounceMail::class] = [
        'extension' => 'rsmbouncemailprocessor',
        'title' => 'RSM process bounce mail',
        'description' => 'This task will process the bounce mail',
    ];

    // clean task queue scheduler
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][CleanTaskQueue::class] = [
        'extension' => 'rsmbouncemailprocessor',
        'title' => 'RSM clean task queue',
        'description' => 'This task will clean the task queue',
    ];
});
