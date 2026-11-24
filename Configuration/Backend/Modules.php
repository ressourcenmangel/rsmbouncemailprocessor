<?php

declare(strict_types=1);

use RSM\Rsmbouncemailprocessor\Controller\BouncemailController;
use RSM\Rsmbouncemailprocessor\Controller\RecipientreportController;

return [
    'web_rsmbouncemailprocessor' => [
        'parent' => 'web',
        // identifier of version 2.x (registerModule() in ext_tables.php), migrates the access rights
        'aliases' => ['web_RsmbouncemailprocessorRsmbouncemailprocessor'],
        'position' => ['after' => 'cutemailing'],
        'access' => 'user',
        'iconIdentifier' => 'apps-rsmbouncemailprocessor',
        'path' => '/module/web/rsmbouncemailprocessor',
        'labels' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_mod_bouncemailprocessor.xlf',
        'extensionName' => 'Rsmbouncemailprocessor',
        'controllerActions' => [
            BouncemailController::class => [
                'list', 'choosePage', 'delete',
            ],
            RecipientreportController::class => [
                'recipientlist', 'choosePage', 'delete',
            ],
        ],
    ],
];
