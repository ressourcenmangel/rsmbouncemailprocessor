<?php

namespace RSM\Rsmbouncemailprocessor\Utility;

use Exception;
use IMAP\Connection;
use RuntimeException;
use RSM\Rsmbouncemailprocessor\Utility\Mailmessage;

/**
 * This library is a wrapper around the Imap library functions included in php. This class in particular manages a
 * connection to the server (imap, pop, etc.) and allows for the easy retrieval of stored messages.
 *
 * @package Mailserver
 * @author  Robert Hafner <tedivm@tedivm.com>
 * @author  Ralph Brugger <ralph.brugger@ressourcenmangel.de>
 */
class Mailserver
{
    /**
     * When SSL isn't compiled into PHP we need to make some adjustments to prevent soul crushing annoyances.
     *
     * @var bool
     */
    public static bool $sslEnable = true;

    /**
     * These are the flags that depend on ssl support being compiled into imap.
     *
     * @var array
     */
    public static array $sslFlags = ['ssl', 'validate-cert', 'novalidate-cert', 'tls', 'notls'];

    /**
     * This is used to prevent the class from putting up conflicting tags. Both directions- key to value, value to key-
     * are checked, so if "novalidate-cert" is passed then "validate-cert" is removed, and vice-versa.
     *
     * @var array
     */
    public static array $exclusiveFlags = ['validate-cert' => 'novalidate-cert', 'tls' => 'notls'];

    /**
     * This is the domain or server path the class is connecting to.
     *
     * @var string
     */
    protected string $serverPath;

    /**
     * This is the name of the current mailbox the connection is using.
     *
     * @var string
     */
    protected string $mailbox = '';

    /**
     * This is the username used to connect to the server.
     *
     * @var string|null
     */
    protected string|null $username;

    /**
     * This is the password used to connect to the server.
     *
     * @var string|null
     */
    protected string|null $password;

    /**
     * This is an array of flags that modify how the class connects to the server. Examples include "ssl" to enforce a
     * secure connection or "novalidate-cert" to allow for self-signed certificates.
     *
     * @link http://us.php.net/manual/en/function.imap-open.php
     * @var array
     */
    protected array $flags = [];

    /**
     * This is the port used to connect to the server
     *
     * @var int
     */
    protected int $port;

    /**
     * This is the set of options, represented by a bitmask, to be passed to the server during connection.
     *
     * @var int
     */
    protected int $options = 0;

    /**
     * This is the set of connection parameters
     *
     * @var array
     */
    protected array $params = [];

    /**
     * This is the Connection to the server. It is required by a number of imap based functions to specify how
     * to connect.
     *
     * @var Connection
     */
    protected Connection $imapStream;

    /**
     * This is the name of the service currently being used. Imap is the default, although pop3 and nntp are also
     * options
     *
     * @var string
     */
    protected string $service = 'imap';

    public function __construct()
    {
    }

    /**
     * This constructor takes the location and service thats trying to be connected to as its arguments.
     *
     * @param string $serverPath
     * @param $user
     * @param $password
     * @param int $port
     * @param string $service # imap or pop3
     * @return Mailserver|null
     */
    public function connect(
        string $serverPath,
        $user,
        $password,
        int $port = 143,
        string $service = 'imap'
    ): Mailserver|null {

        $this->serverPath = $serverPath;
        $this->port = $port;
        $this->service = $service;

        switch ($port) {
            case 143:
                $this->setFlag('novalidate-cert');
                break;

            case 993:
            case 995:
                $this->setFlag('ssl');
                break;
        }

        // set mail username and password
        $this->setAuthentication($user, $password);

        // check if we can connect using the given data
        try {
            $this->getImapStream();
            return $this;
        } catch (Exception) {
            return null;
        }


    }


    /**
     * This function sets the mailbox to connect to.
     *
     * @param string $mailbox
     * @return bool
     */
    public function setMailBox(string $mailbox = ''): bool
    {
        if (!$this->hasMailBox($mailbox)) {
            return false;
        }

        $this->mailbox = $mailbox;
        if (isset($this->imapStream)) {
            $this->setImapStream();
        }

        return true;
    }

    /**
     * This function gets the mailbox as string
     *
     * @return string
     */
    public function getMailBox(): string
    {
        return $this->mailbox;
    }

    /**
     * This function sets or removes flag specifying connection behavior. In many cases the flag is just a one word
     * deal, so the value attribute is not required. However, if the value parameter is passed false it will clear that
     * flag.
     *
     * @param string $flag
     * @param null|string|bool $value
     */
    public function setFlag(string $flag, null|string|bool $value = null): void
    {
        if (!self::$sslEnable && in_array($flag, self::$sslFlags)) {
            return;
        }

        if (isset(self::$exclusiveFlags[$flag])) {
            $kill = self::$exclusiveFlags[$flag];
        } elseif ($index = array_search($flag, self::$exclusiveFlags)) {
            $kill = $index;
        }

        if (isset($kill) && false !== $index = array_search($kill, $this->flags)) {
            unset($this->flags[$index]);
        }

        $index = array_search($flag, $this->flags);
        if (isset($value) && $value !== true) {
            if (!$value && $index !== false) {
                unset($this->flags[$index]);
            } elseif ($value) {
                $match = preg_grep('/' . $flag . '/', $this->flags);
                if (reset($match)) {
                    $this->flags[key($match)] = $flag . '=' . $value;
                } else {
                    $this->flags[] = $flag . '=' . $value;
                }
            }
        } elseif ($index === false) {
            $this->flags[] = $flag;
        }
    }

    /**
     * This funtion is used to set various options for connecting to the server.
     *
     * @param int $bitmask
     * @throws Exception
     */
    public function setOptions(int $bitmask = 0): void
    {
        if (!is_numeric($bitmask)) {
            throw new RuntimeException('Function requires numeric argument.');
        }

        $this->options = $bitmask;
    }

    /**
     * This function is used to set connection parameters
     *
     * @param string $key
     * @param string|array $value
     */
    public function setParam(string $key, string|array $value): void
    {
        $this->params[$key] = $value;
    }

    /**
     * This function gets the current saved imap connection and returns it.
     *
     * @return Connection
     */
    public function getImapStream(): Connection
    {
        if (empty($this->imapStream)) {
            $this->setImapStream();
        }

        return $this->imapStream;
    }

    /**
     * This function takes in all of the connection date (server, port, service, flags, mailbox) and creates the string
     * thats passed to the imap_open function.
     *
     * @return string
     */
    public function getServerString(): string
    {
        $mailboxPath = $this->getServerSpecification();

        if (isset($this->mailbox)) {
            $mailboxPath .= $this->mailbox;
        }

        return $mailboxPath;
    }

    /**
     * Returns the server specification, without adding any mailbox.
     *
     * @return string
     */
    protected function getServerSpecification(): string
    {
        $mailboxPath = '{' . $this->serverPath;

        if (isset($this->port)) {
            $mailboxPath .= ':' . $this->port;
        }

        if ($this->service != 'imap') {
            $mailboxPath .= '/' . $this->service;
        }

        foreach ($this->flags as $flag) {
            $mailboxPath .= '/' . $flag;
        }

        $mailboxPath .= '}';

        return $mailboxPath;
    }

    /**
     * This function creates or reopens an imapStream when called.
     *
     */
    protected function setImapStream(): void
    {
        if (!empty($this->imapStream)) {
            if (!imap_reopen($this->imapStream, $this->getServerString(), $this->options, 1)) {
                throw new RuntimeException(imap_last_error());
            }
        } else {
            $imapStream = @imap_open($this->getServerString(), $this->username, $this->password, $this->options, 1,
                $this->params);

            if ($imapStream === false) {
                throw new RuntimeException(imap_last_error());
            }

            $this->imapStream = $imapStream;
        }
    }

    /**
     * This returns the number of messages that the current mailbox contains.
     *
     * @param string $mailbox
     * @return int
     */
    public function numMessages(string $mailbox = ''): int
    {
        $cnt = 0;
        if ($mailbox === '') {
            $cnt = imap_num_msg($this->getImapStream());
        } elseif ($this->hasMailbox($mailbox)) {
            $oldMailbox = $this->getMailBox();
            $this->setMailbox($mailbox);
            $cnt = $this->numMessages();
            $this->setMailbox($oldMailbox);
        }

        return ((int)$cnt);
    }

    /**
     * This function returns an array of ImapMessage object for emails that fit the criteria passed. The criteria string
     * should be formatted according to the imap search standard, which can be found on the php "imap_search" page or in
     * section 6.4.4 of RFC 2060
     *
     * @link http://us.php.net/imap_search
     * @link http://www.faqs.org/rfcs/rfc2060
     * @param string $criteria
     * @param null|int $limit
     * @return array    An array of ImapMessage objects
     */
    public function search(string $criteria = 'ALL', null|int $limit = null): array
    {
        $messages = [];
        if ($results = imap_search($this->getImapStream(), $criteria, SE_UID)) {
            if (isset($limit) && count($results) > $limit) {
                $results = array_slice($results, 0, $limit);
            }

            foreach ($results as $messageId) {
                $messages[] = new Mailmessage($messageId, $this);
            }
        }
        return $messages;
    }

    /**
     * Returns the emails in the current mailbox as an array of ImapMessage objects.
     *
     * @param null|int $limit
     * @return array
     */
    public function getMessages(null|int $limit = null):array
    {
        $numMessages = $this->numMessages();

        if ($limit > 0 && $limit < $numMessages) {
            $numMessages = $limit;
        }

        if ($numMessages < 1) {
            return [];
        }

        $stream = $this->getImapStream();
        $messages = [];
        for ($i = 1; $i <= $numMessages; $i++) {
            $uid = imap_uid($stream, $i);
            $messages[] = new RSM\Rsmbouncemailprocessor\Utility\Mailmessage($uid, $this);
        }

        return $messages;
    }

    /**
     * This function removes all messages flagged for deletion from the mailbox.
     *
     * @return bool
     */
    public function expunge():bool
    {
        return imap_expunge($this->getImapStream());
    }


    /**
     * This function sets the username and password used to connect to the server.
     *
     * @param string $username
     * @param string $password
     */
    private function setAuthentication(string $username, string $password):void
    {
        $this->username = $username;
        $this->password = $password;
        $this->setParam('DISABLE_AUTHENTICATOR', ['GSSAPI', 'NTLM']);
    }


    /**
     * Checks if the given mailbox exists.
     *
     * @param $mailbox
     *
     * @return bool
     */
    private function hasMailBox($mailbox):bool
    {
        return (boolean)$this->getMailBoxDetails($mailbox);
    }


    /**
     * Return information about the mailbox or mailboxes
     *
     * @param $mailbox
     *
     * @return array
     */
    private function getMailBoxDetails($mailbox):array
    {
        return imap_getmailboxes(
            $this->getImapStream(),
            $this->getServerString(),
            $this->getServerSpecification() . $mailbox
        );
    }

}
