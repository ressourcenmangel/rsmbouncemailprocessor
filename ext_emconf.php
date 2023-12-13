<?php

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
    'description' => 'A RSM TYPO3 extension for bounce mail procsessing',
    'category' => 'plugin',
    'version' => '2.0.0',
    'state' => 'stable',
    'uploadfolder' => false,
    'clearcacheonload' => false,
    'author' => 'ressourcenmangel',
    'author_email' => 'ralph.brugger@ressourcenmangel.de',
    'author_company' => null,
    'constraints' =>
        [
            'depends' =>
                [
                    'typo3' => '11.5.0-11.5.99',
                    'cute_mailing' => '3.0.0-3.99.99',
                ],
            'conflicts' =>
                [
                ],
            'suggests' =>
                [
                ],
        ],
];

