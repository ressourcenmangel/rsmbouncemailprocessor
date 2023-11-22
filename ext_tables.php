<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use RSM\Rsmbouncemailprocessor\Controller\BouncemailController;
use RSM\Rsmbouncemailprocessor\Controller\RecipientreportController;

if (!defined('TYPO3')) {
    die('Access denied.');
}
(function () {


    /**
     * Registers a Backend Module
     */
    ExtensionUtility::registerModule(
        'rsmbouncemailprocessor',
        'web',
        'rsmbouncemailprocessor',    // Submodule key
        'after:cute_mailing',        // Position
        [
            BouncemailController::class => 'list,delete',
            RecipientreportController::class => 'recipientlist,delete',
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:rsmbouncemailprocessor/Resources/Public/Icons/BounceProcessor.svg',
            'labels' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_mod_bouncemailprocessor.xlf',
        ]
    );


})();
