<?php

declare(strict_types=1);

use RSM\Rsmbouncemailprocessor\Controller\BouncemailController;
use RSM\Rsmbouncemailprocessor\Controller\RecipientreportController;

return [
    'web_rsmbouncemailprocessor' => [
        'parent' => 'web',
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
