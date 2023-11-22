<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_recipientreport',
        'label' => 'email',
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
        'iconfile' => 'EXT:rsmbouncemailprocessor/Resources/Public/Icons/tx_rsmbouncemailprocessor_domain_model_recipientreport.svg'
    ],
    'types' => [
        '1' => ['showitem' => 'email,timeprocessed,countunknownreason,countnosenderfound,countuserunknown,countquotaexceeded,countconnectionrefused,countheadererror,countoutofoffice,countfilterlist,countmessagesize,countpossiblespam, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, sys_language_uid, l10n_parent, l10n_diffsource, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access'],
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
                'foreign_table' => 'tx_rsmbouncemailprocessor_domain_model_recipientreport',
                'foreign_table_where' => 'AND {#tx_rsmbouncemailprocessor_domain_model_recipientreport}.{#pid}=###CURRENT_PID### AND {#tx_rsmbouncemailprocessor_domain_model_recipientreport}.{#sys_language_uid} IN (-1,0)',
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

        'email' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_recipientreport.email',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '255',
                'eval' => 'required,trim',
            ],
        ],
        'countunknownreason' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_recipientreport.countunknownreason',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countnosenderfound' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_recipientreport.countnosenderfound',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countuserunknown' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_recipientreport.countuserunknown',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countquotaexceeded' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_recipientreport.countquotaexceeded',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countconnectionrefused' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_recipientreport.countconnectionrefused',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countheadererror' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_recipientreport.countheadererror',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countoutofoffice' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_recipientreport.countoutofoffice',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countfilterlist' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_recipientreport.countfilterlist',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countmessagesize' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_recipientreport.countmessagesize',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],
        'countpossiblespam' => [
            'exclude' => false,
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_recipientreport.countpossiblespam',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'default' => 0
            ],
        ],

    ],
];
