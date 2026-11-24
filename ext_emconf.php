<?php

declare(strict_types=1);

/***************************************************************
 * Extension Manager/Repository config file for ext "rsmbouncemailprocessor".
 *
 * Auto generated 06-02-2023 09:48
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/
$EM_CONF['rsmbouncemailprocessor'] = [
    'title' => 'RSM Bounce Mail Processor',
    'description' => 'A RSM TYPO3 extension for bounce mail processing',
    'category' => 'module',
    'version' => '4.0.0',
    'state' => 'stable',
    'clearcacheonload' => false,
    'author' => 'Ralph Brugger, RYSM GmbH',
    'author_email' => '',
    'author_company' => 'RYSM GmbH',
    'constraints' =>
        [
            'depends' =>
                [
                    'php' => '8.3.0-8.4.99',
                    'typo3' => '13.4.0-14.3.99',
                    'scheduler' => '13.4.0-14.3.99',
                    'cute_mailing' => '4.2.0-4.99.99',
                    'taskqueue' => '8.0.0-9.99.99',
                ],
            'conflicts' =>
                [
                ],
            'suggests' =>
                [
                ],
        ],
];
