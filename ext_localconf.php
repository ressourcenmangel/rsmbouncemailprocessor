<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'rsmbouncemailprocessor',
        'constants',
        "@import 'EXT:rsmbouncemailprocessor/Configuration/TypoScript/constants.typoscript'"
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'rsmbouncemailprocessor',
        'setup',
        "@import 'EXT:rsmbouncemailprocessor/Configuration/TypoScript/setup.typoscript'"
    );

    // bounce mail analyse scheduler
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['RSM\\Rsmbouncemailprocessor\\Task\\AnalyzeBounceMail'] = [
        'extension' => 'rsmbouncemailprocessor',
        'title' => 'RSM analyze bounce mail',
        'description' => 'This task will get bounce mail from the configured mailbox',
        'additionalFields' => 'RSM\\Rsmbouncemailprocessor\\Task\\AnalyzeBounceMailAdditionalFields'
    ];

    // bounce mail process scheduler
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['RSM\\Rsmbouncemailprocessor\\Task\\ProcessBounceMail'] = [
        'extension' => 'rsmbouncemailprocessor',
        'title' => 'RSM process bounce mail',
        'description' => 'This task will process the bounce mail',
    ];

    // clean task queue scheduler
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['RSM\\Rsmbouncemailprocessor\\Task\\CleanTaskQueue'] = [
        'extension' => 'rsmbouncemailprocessor',
        'title' => 'RSM clean task queue',
        'description' => 'This task will clean the task queue',
    ];
});
