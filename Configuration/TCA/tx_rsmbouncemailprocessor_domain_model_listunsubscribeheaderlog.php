<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_listunsubscribeheaderlog',
        'label' => 'email',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
        ],
        'iconfile' => 'EXT:rsmbouncemailprocessor/Resources/Public/Icons/tx_rsmbouncemailprocessor_domain_model_listunsubscribeheaderlog.svg'
    ],

    'types' => [
        '1' => ['showitem' => 'email,deletetime,origuid,origpid, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, sys_language_uid, l10n_parent, l10n_diffsource, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access'],
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
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_rsmbouncemailprocessor_domain_model_listunsubscribeheaderlog',
                'foreign_table_where' => 'AND {#tx_rsmbouncemailprocessor_domain_model_listunsubscribeheaderlog}.{#pid}=###CURRENT_PID### AND {#tx_rsmbouncemailprocessor_domain_model_listunsubscribeheaderlog}.{#sys_language_uid} IN (-1,0)',
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
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_listunsubscribeheaderlog.email',
            'exclude' => false,
            'config' => [
                'type' => 'input',
                'size' => '48',
                'max' => '255',
                'eval' => 'trim',
                'readOnly' => 1,
                'required' => true,
            ],
        ],
        'deletetime' => [
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_listunsubscribeheaderlog.deletetime',
            'exclude' => false,
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'readOnly' => 1,
            ],
        ],

        'origuid' => [
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_listunsubscribeheaderlog.origuid',
            'exclude' => false,
            'config' => [
                'type' => 'input',
                'size' => '10',
                'eval' => 'trim,int',
                'readOnly' => 1,
                'required' => true,
            ],
        ],

        'origpid' => [
            'label' => 'LLL:EXT:rsmbouncemailprocessor/Resources/Private/Language/locallang_db.xlf:tx_rsmbouncemailprocessor_domain_model_listunsubscribeheaderlog.origpid',
            'exclude' => false,
            'config' => [
                'type' => 'input',
                'size' => '10',
                'eval' => 'trim,int',
                'readOnly' => 1,
                'required' => true,
            ],
        ],


    ],
];
