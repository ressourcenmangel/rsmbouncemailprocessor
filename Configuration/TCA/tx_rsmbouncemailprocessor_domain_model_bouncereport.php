<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_bouncereport',
        'label' => 'timeprocessed',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
        ],
        'iconfile' => 'EXT:rsmbouncemailprocessor/Resources/Public/Icons/tx_rsmbouncemailprocessor_domain_model_bouncereport.svg'
    ],
    'types' => [
        '1' => ['showitem' => 'newsletterid,timeprocessed,countmails,countprocessed,countunknownreason,countnosenderfound,countuserunknown,countquotaexceeded,countconnectionrefused,countheadererror,countoutofoffice,countfilterlist,countmessagesize,countpossiblespam, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, sys_language_uid, l10n_parent, l10n_diffsource, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access'],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => false,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 0,
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_rsmbouncemailprocessor_domain_model_bouncereport',
                'foreign_table_where' => 'AND {#tx_rsmbouncemailprocessor_domain_model_bouncereport}.{#pid}=###CURRENT_PID### AND {#tx_rsmbouncemailprocessor_domain_model_bouncereport}.{#sys_language_uid} IN (-1,0)',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'crdate' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'tstamp' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],

        'newsletterid' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_bouncereport.newsletterid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_cutemailing_domain_model_newsletter',
                'foreign_table_where' => ' ORDER BY tx_cutemailing_domain_model_newsletter.title',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],

        'timeprocessed' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_bouncereport.timeprocessed',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 10,
                'eval' => 'datetime',
                'default' => 0
            ],
        ],

        'countmails' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_bouncereport.countmails',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countprocessed' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_bouncereport.countprocessed',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countunknownreason' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_bouncereport.countunknownreason',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countnosenderfound' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_bouncereport.countnosenderfound',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countuserunknown' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_bouncereport.countuserunknown',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countquotaexceeded' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_bouncereport.countquotaexceeded',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countconnectionrefused' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_bouncereport.countconnectionrefused',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countheadererror' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_bouncereport.countheadererror',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countoutofoffice' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_bouncereport.countoutofoffice',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countfilterlist' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_bouncereport.countfilterlist',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countmessagesize' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_bouncereport.countmessagesize',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countpossiblespam' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_bouncereport.countpossiblespam',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],

    ],
];
