<?php
declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Task;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use RSM\Rsmbouncemailprocessor\Utility\Mailserver;
use RSM\Rsmbouncemailprocessor\Utility\Mailmessage;
use Undkonsorten\CuteMailing\Domain\Repository\SendOutRepository;
use Undkonsorten\CuteMailing\Domain\Repository\NewsletterRepository;
use RSM\Rsmbwcutemailingextend\Domain\Repository\TtAddressRecipientRepository;

define('SENDER_ORG', 'Sender is org recipient');
define('SENDER_FROM', 'Sender is from');
define('SENDER_MISSING', 'Sender is missing');

define('ERR_REASON', [
    '-1' => 'unknown reason',
    '-2' => 'no sender found',
    '550' => 'user unknown',
    '551' => 'quota exceeded',
    '552' => 'connection refused',
    '554' => 'header error',
    'XOUT' => 'out of office',
    'XFILTER' => 'Filterlist',
    'XSIZE' => 'Mesage size',
    'XSPAM' => 'Possible Spam',
]);

/**
 * Class AnalyzeBounceMail
 * @package RSM\Rsmbouncemailprocessor\Scheduler
 * @author Ralph Brugger <ralph.brugger@ressourcenmangel.de>
 */
class AnalyzeBounceMail extends AbstractTask
{

    protected $arReasontext = [
        '550' => [
            '5.7.1 unable to relay',
            '550 5.1.1 recipient',
        ],
        '551' => [
            'ist voll und kann zurzeit keine nachrichten',
            'mailbox full',
        ],
        '552' => [
            'connection refused',
            'connection timed out',
        ],
        '554' => [
            'error in header',
            'header error',
        ],
        'XOUT' => [
            'abwesenheitsnotiz',
            'abwesend und kehre zur',
        ],
        'XFILTER' => [
            'email address is on senderfilterconfig list',
            'message bounced due to organizational settings',
        ],
        'XSIZE' => [
            'verkleinern Sie die Nachricht, beispielsweise durch Entfernen'
        ],
        'XSPAM' => [
            'as spam'
        ],
    ];


    /**
     * bounces array
     * @var array
     */
    protected array $arBounces = [];

    /**
     * messages array
     * @var array
     */
    protected array $messages = [];

    /**
     * reports array
     * @var array
     */
    protected array $reports = [];

    /**
     * mailserver object
     * @var null|Mailserver
     */
    protected null|Mailserver $mailServer;


    /**
     * sendOutRepository object
     * @var SendOutRepository
     */
    protected SendOutRepository $sendOutRepository;

    /**
     * newsletterRepository object
     * @var NewsletterRepository
     */
    protected NewsletterRepository $newsletterRepository;

    /**
     * @var RecipientRepository|null
     */
    protected $recipientRepository = null;

    /**
     * @var PersistenceManager
     */
    protected $persistenceManager = null;


    /**
     * url of the mail server
     * @var string
     */
    protected string $server;

    /**
     * Port number of the mail server
     * @var int
     */
    protected int $port;

    /**
     * Username to use to authenticate
     * @var string
     */
    protected string $user;

    /**
     * Password of the user
     * @var string
     */
    protected string $password;

    /**
     * Mailserver service (imap or pop3)
     * @var string
     */
    protected string $service;

    /**
     * Maximum number of bounce mail to be processed
     * @var int
     */
    protected int $maxProcessed;

    /**
     * Delete every mail after processing
     * @var bool
     */
    protected bool $deletealways = false;

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort(int $port)
    {
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     */
    public function setUser(string $user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getService(): string
    {
        return $this->service;
    }

    /**
     * @param string $service
     */
    public function setService(string $service)
    {
        $this->service = $service;
    }

    /**
     * @return string
     */
    public function getServer(): string
    {
        return $this->server;
    }

    /**
     * @param string $server
     */
    public function setServer(string $server)
    {
        $this->server = $server;
    }

    /**
     * @return int
     */
    public function getMaxProcessed(): int
    {
        return $this->maxProcessed;
    }

    /**
     * @param mixed $maxProcessed
     */
    public function setMaxProcessed(int $maxProcessed)
    {
        $this->maxProcessed = (int)$maxProcessed;
    }

    /**
     * @return bool
     */
    public function getDeletealways(): bool
    {
        return $this->deletealways;
    }

    /**
     * @param bool $deletealways
     */
    public function setDeletealways(bool $deletealways): void
    {
        $this->deletealways = $deletealways;
    }


    /**
     * initializes the class
     *
     */
    public function initClass(): void
    {

        // TS Setup
        $this->conf = $this->getModuleTs('tx_rsmbouncemailprocessor');

        // set the reason texts from TS
        $this->arReasontext = [];
        foreach (ERR_REASON as $key => $value) {
            if (isset($this->conf['settings.']['reasontext.']["$key."])) {
                $this->arReasontext[$key] = $this->conf['settings.']['reasontext.']["$key."];
            }
        }

        /** @var Mailserver $mailServer */
        $this->mailServer = GeneralUtility::makeInstance(Mailserver::class);

        /** @var SendOutRepository $sendOutRepository */
        $this->sendOutRepository = GeneralUtility::makeInstance(SendOutRepository::class);


        /** @var NewsletterRepository $newsletterRepository */
        $this->newsletterRepository = GeneralUtility::makeInstance(NewsletterRepository::class);

        /**  @var TtAddressRecipientRepository $recipientRepository */
        $this->recipientRepository = GeneralUtility::makeInstance(TtAddressRecipientRepository::class);

        /** @var PersistenceManager $persistenceManager */
        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        $this->recipientRepository->injectPersistenceManager($this->persistenceManager);

    }

    /**
     * execute the scheduler task.
     *
     * @return bool
     */
    public function execute(): bool
    {

        // defaults
        $result = false;

        // init
        $this->initClass();

        // try to connect to mail server
        $this->mailServer->connect(
            $this->server,
            $this->user,
            $this->password,
            (int)$this->port,
            $this->service
        );

        // proceed with the mails
        if ($this->mailServer instanceof Mailserver) {

            // get the messages from the server stream
            $this->messages = $this->mailServer->search('UNSEEN', $this->maxProcessed);

            /** @var Message $message The message object */
            foreach ($this->messages as $message) {

                // process the mail
                $listunsubscribeHeader = $this->getListunsubscribeHeader($message);
                if ($listunsubscribeHeader) {
                    $result = $this->processlistunsubscribeHeader($message, $listunsubscribeHeader);

                } else {
                    $result = $this->processBounceMail($message);

                }

                // delete the mail or set it to seen
                if ($result || $this->getDeletealways()) {
                    $message->delete();
                } else {
                    $message->setFlag('SEEN');
                }
            }

            // expunge to delete permanently
            $this->mailServer->expunge();
            imap_close($this->mailServer->getImapStream());
            $result = true;
        }

        // create reports
        $this->createReport();

        // save report "Bounces per NL"
        $this->saveReportBouncePerNl();

        // save report "Bounces per Recipient"
        $this->saveReportBouncePerRecipient();

        return $result;

    }

    /**
     * Process the bounce mail
     * @param Mailmessage $mailmessage the message object
     * @return bool true if bounce mail can be parsed, else false
     */
    private function processBounceMail(Mailmessage $mailmessage)
    {

        // defaults
        $orgrecipient = '';
        $typo3nluid = null;
        $bounceItem = [];

        // read content once
        $subject = $mailmessage->getSubject();
        $header = $this->getRawHeaders($mailmessage->getUid());
        $body = $this->imapGetmsg($mailmessage->getUid());

        // Cleanup linebreaks
        $bodyclean = str_replace("\r", ' ', $body);
        $bodyclean = str_replace("\n", ' ', $bodyclean);
        $bodyclean = trim(preg_replace('/\s+/', ' ', $bodyclean));

        //
        // search for the final recipient
        //

        // search in header only
        $orgrecipient = $this->getMailFinalRecipient($header);

        // search in whole body
        if (!$orgrecipient) {
            $orgrecipient = $this->getMailFinalRecipient($bodyclean);
        }

        // If no final recipient take the "header: from"
        if (!$orgrecipient) {
            $from = $mailmessage->getAddresses("from");
            if (isset($from)) {
                if (isset($from['address'])) {
                    if (strpos($from['address'], '@') !== false) {
                        $bounceItem['sender'] = SENDER_FROM;
                        $orgrecipient = $from['address'];
                    }
                }
            }
        }

        //
        // search for the TYPO3 Newsletter uid
        //

        // search in header only
        $sendOutUid = $this->getMailNewsletterUID($header);

        // search in whole body
        if (!$sendOutUid) {
            $sendOutUid = $this->getMailNewsletterUID($bodyclean);
        }

        // get the sendout from the uid
        $sendout = null;
        if ($sendOutUid) {
            $sendout = $this->sendOutRepository->findByUid($sendOutUid);
        }

        // get the newsletter from the sendout
        $newsletter = null;
        if ($sendout) {
            $newsletter = $sendout->getNewsletter();
        }

        // set the newsletter to the item
        $bounceItem['nluid'] = 0;
        if ($newsletter) {
            $bounceItem['nluid'] = $newsletter->getUid();
        }

        // No sender at all, return, we don't need the reason
        if (!$orgrecipient) {
            $bounceItem['sender'] = SENDER_MISSING;
            $bounceItem['reason_id'] = "-2";
            $bounceItem['reason_text'] = ERR_REASON[-2];
            $bounceItem['recipient'] = '';
            $this->arBounces[] = $bounceItem;
            return false;
        }

        // Search for the reason
        $bounceReport = $this->analyseReturnError($subject);
        if ($bounceReport['reason'] == '-1') {
            $bounceReport = $this->analyseReturnError($header);
        }
        if ($bounceReport['reason'] == '-1') {
            $bounceReport = $this->analyseReturnError($bodyclean);
        }

        // Save BounceItems
        $bounceItem['sender'] = SENDER_ORG;
        $bounceItem['reason_id'] = $bounceReport['reason'];
        $bounceItem['reason_text'] = ERR_REASON[$bounceReport['reason']];
        $bounceItem['recipient'] = $orgrecipient;
        $this->arBounces[] = $bounceItem;

        return true;
    }


    /**
     * creates the bounce reports
     */
    private function createReport(): void
    {

        // create report per nluid & reason
        foreach ($this->arBounces as $bounceItem) {
            $this->reports[$bounceItem['nluid']][$bounceItem['reason_id']][] = $bounceItem;
        }
    }

    /**
     * saves the bounce per Newsletter reports
     */
    private function saveReportBouncePerNl(): void
    {
        // defaults
        $table = 'tx_rsmbouncemailprocessor_domain_model_bouncereport';
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $timestamp = time();

        // Save one report for each nluid
        foreach ($this->reports as $nluid => $reportByNL) {

            // calculate the totals of processed items
            $counttotal = 0;
            foreach ($reportByNL as $reportItem) {
                $counttotal = $counttotal + count($reportItem);
            }

            // do not save when the nluid wasn't found
            if ($nluid) {
                $newsletter = $this->newsletterRepository->findByUid($nluid);

                // get the pid from the newsletteruid
                if ($newsletter) {
                    $pid = $newsletter->getPid();
                }

                // do not save when nothing processed
                if ($counttotal && $pid) {

                    // defaults
                    $uidread = null;

                    $countmails = 0;
                    $countprocessed = 0;
                    $countunknownreason = 0;
                    $countnosenderfound = 0;
                    $countuserunknown = 0;
                    $countquotaexceeded = 0;
                    $countconnectionrefused = 0;
                    $countheadererror = 0;
                    $countoutofoffice = 0;
                    $countfilterlist = 0;
                    $countmessagesize = 0;
                    $countpossiblespam = 0;

                    // Read existing entry for this newsletter
                    $resultRows = $connection
                        ->select(
                            ['*'],
                            $table,
                            ['newsletterid' => $nluid]
                        );

                    if ($resultRows) {
                        $row = $resultRows->fetchAssociative();
                        if ($row) {
                            $uidread = $row['uid'];
                            $countmails = $row['countmails'];
                            $countprocessed = $row['countprocessed'];
                            $countunknownreason = $row['countunknownreason'];
                            $countnosenderfound = $row['countnosenderfound'];
                            $countuserunknown = $row['countuserunknown'];
                            $countquotaexceeded = $row['countquotaexceeded'];
                            $countconnectionrefused = $row['countconnectionrefused'];
                            $countheadererror = $row['countheadererror'];
                            $countoutofoffice = $row['countoutofoffice'];
                            $countfilterlist = $row['countfilterlist'];
                            $countmessagesize = $row['countmessagesize'];
                            $countpossiblespam = $row['countpossiblespam'];
                        }
                    }

                    // add current data
                    $countmails += count($this->messages);
                    $countprocessed += $counttotal;
                    $countunknownreason += count($reportByNL['-1'] ?? []);
                    $countnosenderfound += count($reportByNL['-2'] ?? []);
                    $countuserunknown += count($reportByNL['550'] ?? []);
                    $countquotaexceeded += count($reportByNL['551'] ?? []);
                    $countconnectionrefused += count($reportByNL['552'] ?? []);
                    $countheadererror += count($reportByNL['554'] ?? []);
                    $countoutofoffice += count($reportByNL['XOUT'] ?? []);
                    $countfilterlist += count($reportByNL['XFILTER'] ?? []);
                    $countmessagesize += count($reportByNL['XSIZE'] ?? []);
                    $countpossiblespam += count($reportByNL['XSPAM'] ?? []);

                    // set up the data
                    $tabeldata =
                        [
                            'pid' => $pid,
                            'tstamp' => $timestamp,
                            'timeprocessed' => $timestamp,
                            'countmails' => $countmails,
                            'countprocessed' => $countprocessed,
                            'countunknownreason' => $countunknownreason,
                            'countnosenderfound' => $countnosenderfound,
                            'countuserunknown' => $countuserunknown,
                            'countquotaexceeded' => $countquotaexceeded,
                            'countconnectionrefused' => $countconnectionrefused,
                            'countheadererror' => $countheadererror,
                            'countoutofoffice' => $countoutofoffice,
                            'countfilterlist' => $countfilterlist,
                            'countmessagesize' => $countmessagesize,
                            'countpossiblespam' => $countpossiblespam,
                        ];

                    // INSERT
                    if (!$uidread) {
                        $tabeldata['crdate'] = $timestamp;
                        $tabeldata['newsletterid'] = $nluid;
                        $connection->insert($table, $tabeldata);

                        // UPDATE
                    } else {
                        $connection->update($table, $tabeldata, ['uid' => $uidread]);
                    }
                }

            }

        }
    }

    /**
     * saves the bounce per recipient reports
     */
    private function saveReportBouncePerRecipient(): void
    {
        // defaults
        $table = 'tx_rsmbouncemailprocessor_domain_model_recipientreport';
        $timestamp = time();

        // Objects
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);

        // Save one reort for each nluid
        foreach ($this->reports as $nluid => $reportByNL) {

            $pid = 2;
            if ($nluid) {
                $newsletter = $this->newsletterRepository->findByUid($nluid);
                if ($newsletter) {
                    $pid = $newsletter->getPid();
                }
            }

            foreach ($reportByNL as $bounceReasons) {
                foreach ($bounceReasons as $bounceItem) {

                    // defaults
                    $uidread = null;
                    $countunknownreason = 0;
                    $countnosenderfound = 0;
                    $countuserunknown = 0;
                    $countquotaexceeded = 0;
                    $countconnectionrefused = 0;
                    $countheadererror = 0;
                    $countoutofoffice = 0;
                    $countfilterlist = 0;
                    $countmessagesize = 0;
                    $countpossiblespam = 0;

                    // set data
                    $email = $bounceItem['recipient'];
                    $reason_id = $bounceItem['reason_id'];

                    // search for the sender, and take ghis current data
                    $resultRows = $connection
                        ->select(
                            ['*'],
                            $table,
                            ['email' => $email]
                        );
                    if ($resultRows) {
                        $row = $resultRows->fetchAssociative();
                        if ($row) {
                            $uidread = $row['uid'];
                            $countunknownreason = $row['countunknownreason'];
                            $countnosenderfound = $row['countnosenderfound'];
                            $countuserunknown = $row['countuserunknown'];
                            $countquotaexceeded = $row['countquotaexceeded'];
                            $countconnectionrefused = $row['countconnectionrefused'];
                            $countheadererror = $row['countheadererror'];
                            $countoutofoffice = $row['countoutofoffice'];
                            $countfilterlist = $row['countfilterlist'];
                            $countmessagesize = $row['countmessagesize'];
                            $countpossiblespam = $row['countpossiblespam'];
                        }
                    }

                    // raise the count depending the reason
                    switch ($reason_id) {

                        # unknown reason
                        case '-2':
                            $countunknownreason++;
                            break;

                        # no sender found
                        case '-1':
                            $countnosenderfound++;
                            break;

                        # user unknown
                        case '550':
                            $countuserunknown++;
                            break;

                        # quota exceeded
                        case '551':
                            $countquotaexceeded++;
                            break;

                        # connection refused
                        case '552':
                            $countconnectionrefused++;
                            break;

                        # header error
                        case '554':
                            $countheadererror++;
                            break;

                        # out of office
                        case 'XOUT':
                            $countoutofoffice++;
                            break;

                        # Filterlist
                        case 'XFILTER':
                            $countfilterlist++;
                            break;

                        # Mesage size
                        case 'XSIZE':
                            $countmessagesize++;
                            break;

                        # Possible Spam
                        case 'XSPAM':
                            $countpossiblespam++;
                            break;
                    }

                    $tabeldata =
                        [
                            'pid' => $pid,
                            'tstamp' => $timestamp,
                            'countunknownreason' => $countunknownreason,
                            'countnosenderfound' => $countnosenderfound,
                            'countuserunknown' => $countuserunknown,
                            'countquotaexceeded' => $countquotaexceeded,
                            'countconnectionrefused' => $countconnectionrefused,
                            'countheadererror' => $countheadererror,
                            'countoutofoffice' => $countoutofoffice,
                            'countfilterlist' => $countfilterlist,
                            'countmessagesize' => $countmessagesize,
                            'countpossiblespam' => $countpossiblespam,
                        ];

                    // INSERT
                    if (!$uidread) {
                        $tabeldata['crdate'] = $timestamp;
                        $tabeldata['email'] = $email;
                        $connection->insert($table, $tabeldata);

                        // UPDATE
                    } else {
                        $connection->update($table, $tabeldata, ['uid' => $uidread]);
                    }

                }

            }
        }
    }


    /**
     * Analyses the return-mail content
     * used to find what reason there was for rejecting the mail
     *
     * @param string $c Message Body/text
     *
     * @return array  key/value pairs with analysis result.
     *  Eg. "reason", "content", "reason_text",
     */
    public function analyseReturnError($c)
    {
        $cp = [];
        // QMAIL
        if (preg_match('/' . preg_quote('--- Below this line is a copy of the message.') . '|' . preg_quote('------ This is a copy of the message, including all the headers.') . '/i',
            $c)) {
            if (preg_match('/' . preg_quote('--- Below this line is a copy of the message.') . '/i', $c)) {
                // Splits by the QMAIL divider
                $parts = explode('-- Below this line is a copy of the message.', $c, 2);
            } else {
                // Splits by the QMAIL divider
                $parts = explode('------ This is a copy of the message, including all the headers.', $c, 2);
            }
            $cp['content'] = trim($parts[0]);
            $parts = explode('>:', $cp['content'], 2);
            if (isset($parts[1])) {
                $cp['reason_text'] = trim($parts[1]);
            } else {
                $cp['reason_text'] = $cp['content'];
            }
            $cp['reason'] = $this->extractReason($cp['reason_text']);

        } elseif (strstr($c, 'The Postfix program')) {
            // Postfix
            $cp['content'] = trim($c);
            $parts = explode('>:', $c, 2);
            $cp['reason_text'] = trim($parts[1]);
            // 550 Invalid recipient, User unknown
            if (stristr($cp['reason_text'], '550')) {
                $cp['reason'] = 550;
                // No such user
            } elseif (stristr($cp['reason_text'], '553')) {
                $cp['reason'] = 553;
                // Mailbox full
            } elseif (stristr($cp['reason_text'], '551')) {
                $cp['reason'] = 551;
                // Mailbox full
            } elseif (stristr($cp['reason_text'], 'recipient storage full')) {
                $cp['reason'] = 551;
            } else {
                $cp['reason'] = -1;
            }
//
        } elseif (strstr($c, 'Your message cannot be delivered to the following recipients:')) {
            // whoever this is...
            $cp['content'] = trim($c);
            $cp['reason_text'] = trim(strstr($cp['content'],
                'Your message cannot be delivered to the following recipients:'));
            $cp['reason_text'] = trim(substr($cp['reason_text'], 0, 500));
            $cp['reason'] = $this->extractReason($cp['reason_text']);

        } elseif (strstr($c, 'Diagnostic-Code: X-Notes')) {
            // Lotus Notes
            $cp['content'] = trim($c);
            $cp['reason_text'] = trim(strstr($cp['content'], 'Diagnostic-Code: X-Notes'));
            $cp['reason_text'] = trim(substr($cp['reason_text'], 0, 200));
            $cp['reason'] = $this->extractReason($cp['reason_text']);

        } elseif (strstr($c, 'Send feedback to Microsoft')) {
            // Outlook
            $cp['content'] = trim($c);
            $cp['reason_text'] = trim(strstr($cp['content'], 'Send feedback to Microsoft'));
            $cp['reason_text'] = trim(substr($cp['reason_text'], 0, 400));
            $cp['reason'] = $this->extractReason($cp['reason_text']);

        } else {
            // No-named:
            $cp['content'] = trim($c);
            $cp['reason_text'] = trim(substr($c, 0, 1000));
            $cp['reason'] = $this->extractReason($cp['reason_text']);
        }

        return $cp;
    }

    /**
     * Try to match reason found in the returned email
     * with the defined reasons (see $reason_text)
     *
     * @param string $text Content of the returned email
     *
     * @return string  The error code.
     */
    public function extractReason($text): string
    {

        foreach ($this->arReasontext as $key => $errorclasses) {
            foreach ($errorclasses as $textvalues) {
                if (stripos($text, $textvalues) !== false) {
                    return strval($key);
                }
            }
        }

        return "-1";
    }


    /*
     * Try to find the final recipient in the text
     * either as base64 encoded X-TYPO3RCPT or as final-recipient
     *
     * @param string $content content that will be searched
     *
     * @return string the final recipient
     */
    public function getMailFinalRecipient(string $content): string
    {

        $xtypo3 = $this->searchString($content, 'x-typo3rcpt:');
        if ($xtypo3) {
            $xtypo3 = base64_decode($xtypo3);
        }

        if (!$xtypo3) {
            $xtypo3 = $this->searchString($content, 'x-failed-recipients:');
        }

        if (!$xtypo3) {
            $xtypo3 = $this->searchString($content, 'final-recipient: rfc822;');
        }

        if (!$xtypo3) {
            $xtypo3 = $this->searchString($content, 'final-recipient:');
        }

        $xtypo3 = str_replace('rfc822;', '', $xtypo3);

        if (!str_contains($xtypo3, '@')) {
            $xtypo3 = '';
        }

        return $xtypo3;
    }


    /*
     * Try to find the TYPO3 Newsletter uid in the text
     * as X-TYPO3NLUID
     *
     * @param string $content content that will be searched
     *
     * @return int|null the TYPO3 Newsletter uid, returns 1 if not found
     */
    public function getMailNewsletterUID(string $content): int|null
    {

        $xtypo3 = $this->searchString($content, 'x-typo3nluid:');
        $xtypo3 = intval($xtypo3);
        if (!$xtypo3) {
            $xtypo3 = null;
        }

        return $xtypo3;
    }

    /**
     * Searches the string $search in the $subject
     *
     * @param string $content content string where we search in
     * @param string $search string that we're searching for
     *
     * @return string the final recipient
     */
    private function searchString(string $content, string $search): string
    {
        $found = '';
        $iPos1 = stripos($content, $search);
        if ($iPos1 > 0) {
            $iPos1 += strlen($search) + 1;
            if ($iPos1 < strlen($content)) {
                $iPos2 = stripos($content, ' ', $iPos1);
                $iPos3 = stripos($content, "\n", $iPos1);
                if ($iPos3 < $iPos2 && $iPos3 > 0) {
                    $iPos2 = $iPos3;
                }
                if ($iPos2 > $iPos1) {
                    $found = trim(substr($content, $iPos1, $iPos2 - $iPos1));
                }

            }
        }
        return $found;
    }

    /**
     * // https://www.php.net/manual/en/function.imap-fetchstructure.php
     * gets a imap message
     *
     * @param int $mid the message id
     *
     * @return string the final recipient
     */
    function imapGetmsg(int $mid): string
    {

        // input $mbox = IMAP stream, $mid = message id
        // output all the following:
        $plainmsg = '';

        // BODY
        $structure = imap_fetchstructure($this->mailServer->getImapStream(), $mid);
        if (!isset($structure->parts)) {
            // getpart($mbox,$mid,$s,0);  // pass 0 as part-number
            $plainmsg .= $this->imapGetPart($mid, $structure, 0, $plainmsg);  // pass 0 as part-number
        } else {  // multipart: cycle through each part
            foreach ($structure->parts as $partno0 => $part) {
                // getpart($mbox,$mid,$p,$partno0+1);
                $plainmsg .= $this->imapGetPart($mid, $part, $partno0 + 1, $plainmsg);
            }
        }

        return $plainmsg;
    }

    // https://www.php.net/manual/en/function.imap-fetchstructure.php
    function imapGetPart($mid, $part, $partno, $plainmsg)
    {

        // $partno = '1', '2', '2.1', '2.1.3', etc for multipart, 0 if simple

        // DECODE DATA
        $data = ($partno) ? imap_fetchbody($this->mailServer->getImapStream(), $mid,
            strval($partno)) : imap_body($this->mailServer->getImapStream(), $mid);

        // Any part may be encoded, even plain text messages, so check everything.
        if ($part->encoding == 4) {
            $data = quoted_printable_decode($data);

        } elseif ($part->encoding == 3) {
            $data = base64_decode($data);
        }

        // PARAMETERS
        // get all parameters, like charset, filenames of attachments, etc.
        $params = [];
        if (isset($part->parameters)) {
            foreach ($part->parameters as $x) {
                $params[strtolower($x->attribute)] = $x->value;

            }
        }
        if (isset($part->dparameters)) {
            foreach ($part->dparameters as $x) {
                $params[strtolower($x->attribute)] = $x->value;
            }
        }


        // TEXT
        if ($part->type == 0 && $data) {
            // Messages may be split in different parts because of inline attachments,
            // so append parts together with blank row.
            $plainmsg .= trim($data) . "\n\n";
        }

        // EMBEDDED MESSAGE
        // There are no PHP functions to parse embedded messages,
        // so this just appends the raw source to the main message.
        elseif ($part->type == 2 && $data) {
            $plainmsg .= $data . "\n\n";
        }

        // SUBPART RECURSION
        if (isset($part->parts)) {
            foreach ($part->parts as $partno0 => $p2) {
                $this->imapGetPart($mid, $p2, $partno . '.' . ($partno0 + 1), $plainmsg);  // 1.2, 1.2.1, etc.

            }
        }

        return $plainmsg;
    }

    public function getRawHeaders($uid)
    {
        return imap_fetchheader($this->mailServer->getImapStream(), $uid, FT_UID);
    }

    /**
     * get the ListunsubscribeHeader
     * @param Mailmessage $mailmessage the message object
     * @return array|null
     */
    private function getListunsubscribeHeader(Mailmessage $mailmessage): array|null
    {

        $unsubscribe = null;

        if ($mailmessage) {

            // parse the subject
            $unsubscribe = [];
            $subject = $mailmessage->getSubject();

            $arSubjectLevel1 = explode('&', $subject);
            foreach ($arSubjectLevel1 as $subjectLevel1) {
                $arSubjectLevel2 = explode('=', $subjectLevel1);
                if (isset($arSubjectLevel2[0]) && isset($arSubjectLevel2[1])) {
                    $unsubscribe[$arSubjectLevel2[0]] = $arSubjectLevel2[1];
                }
            }

            // check the array
            if (!isset($unsubscribe['action'])
                || !isset($unsubscribe['rcptuid'])
                || !isset($unsubscribe['rcptemail'])
                || !isset($unsubscribe['sendout'])) {
                $unsubscribe = null;
            }

            // check the values
            if ($unsubscribe) {
                if ($unsubscribe['action'] !== 'listunsubscribe') {
                    $unsubscribe = null;
                }
            } else {
                $unsubscribe = null;
            }
            if ($unsubscribe) {
                $unsubscribe['rcptuid'] = intval($unsubscribe['rcptuid']);
                $unsubscribe['sendout'] = intval($unsubscribe['sendout']);
                if ($unsubscribe['rcptuid'] <= 0 || $unsubscribe['sendout'] <= 0) {
                    $unsubscribe = null;

                }
            }

        }

        return $unsubscribe;
    }

    /**
     * process the ListunsubscribeHeader
     * @param Array $listunsubscribeHeader
     * @return bool
     */
    private function processlistunsubscribeHeader(array $listunsubscribeHeader): bool
    {
        $success = false;

        if ($listunsubscribeHeader) {

            // get the recipient
            $defaultQuerySettings = $this->recipientRepository->createQuery()->getQuerySettings();
            $defaultQuerySettings->setRespectStoragePage(false);
            $this->recipientRepository->setDefaultQuerySettings($defaultQuerySettings);

            /** @var RegisteraddressRecipientList $recipient */
            $recipient = $this->recipientRepository->findByUid($listunsubscribeHeader['rcptuid']);
            if ($recipient) {

                // compare the email
                $email = $recipient->getEmail();
                if ($email && strtolower($email) == strtolower($listunsubscribeHeader['rcptemail'])) {
                    $logpid = $recipient->getPid() ?? 0;

                    // delete the $recipient && persist
                    $this->recipientRepository->remove($recipient);
                    $this->persistenceManager->persistAll();

                        // delete log enabled?
                        if (isset($this->conf['settings.']['deletelog.']['enabled']) && isset($this->conf['settings.']['deletelog.']['pid'])) {
                            if ($this->conf['settings.']['deletelog.']['enabled'] == 1) {
                                if ($this->conf['settings.']['deletelog.']['pid'] > 0) {

                                    // write delete log entry (tx_rsmbouncemailprocessor_domain_model_listunsubscribeheaderlog)
                                    $queryBuilderAddLog = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_rsmbouncemailprocessor_domain_model_listunsubscribeheaderlog');
                                    $affectedRows = $queryBuilderAddLog
                                        ->insert('tx_rsmbouncemailprocessor_domain_model_listunsubscribeheaderlog')
                                        ->values([
                                            'pid' => $this->conf['settings.']['deletelog.']['pid'],
                                        'email' => $listunsubscribeHeader['rcptemail'],
                                        'origuid' => $listunsubscribeHeader['rcptuid'],
                                            'origpid' => $logpid,
                                        'tstamp' => time(),
                                        'crdate' => time(),
                                            'deletetime' => time(),
                                        ])
                                        ->executeStatement();
                                }

                            }
                        }
                    }
                }


        }

        return $success;
    }

    /**
     * returns the TS settings for a specific path
     * @param string $path the path
     * @return array
     */
    private function getModuleTs($path): array
    {
        $mysettings = [];

        $configurationManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::class);
        $settings = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT,
            'rsmbouncemailprocessor');

        if (isset($settings['module.']["$path."])) {
            $mysettings = $settings['module.']["$path."];
        }
        return $mysettings;
    }

}
