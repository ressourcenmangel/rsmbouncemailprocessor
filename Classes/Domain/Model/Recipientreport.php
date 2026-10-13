<?php

namespace RSM\Rsmbouncemailprocessor\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Recipientreport extends AbstractEntity
{
    /**
     * @var string
     */
    protected $email;

    /**
     * @var int
     */
    protected $countunknownreason = 0;

    /**
     * @var int
     */
    protected $countnosenderfound = 0;

    /**
     * @var int
     */
    protected $countuserunknown = 0;

    /**
     * @var int
     */
    protected $countquotaexceeded = 0;

    /**
     * @var int
     */
    protected $countconnectionrefused = 0;

    /**
     * @var int
     */
    protected $countheadererror = 0;

    /**
     * @var int
     */
    protected $countoutofoffice = 0;

    /**
     * @var int
     */
    protected $countfilterlist = 0;

    /**
     * @var int
     */
    protected $countmessagesize = 0;

    /**
     * @var int
     */
    protected $countpossiblespam = 0;


    /**
     * @var int
     */
    protected $countsum = 0;

    /**
     * Timestamp at which the process task removed the address from the recipient lists, 0 if not removed
     *
     * @var int
     */
    protected $removed = 0;


    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }


    /**
     * @return int
     */
    public function getCountunknownreason(): int
    {
        return $this->countunknownreason;
    }

    /**
     * @param int $countunknownreason
     */
    public function setCountunknownreason(int $countunknownreason): void
    {
        $this->countunknownreason = $countunknownreason;
    }

    /**
     * @return int
     */
    public function getCountnosenderfound(): int
    {
        return $this->countnosenderfound;
    }

    /**
     * @param int $countnosenderfound
     */
    public function setCountnosenderfound(int $countnosenderfound): void
    {
        $this->countnosenderfound = $countnosenderfound;
    }

    /**
     * @return int
     */
    public function getCountuserunknown(): int
    {
        return $this->countuserunknown;
    }

    /**
     * @param int $countuserunknown
     */
    public function setCountuserunknown(int $countuserunknown): void
    {
        $this->countuserunknown = $countuserunknown;
    }

    /**
     * @return int
     */
    public function getCountquotaexceeded(): int
    {
        return $this->countquotaexceeded;
    }

    /**
     * @param int $countquotaexceeded
     */
    public function setCountquotaexceeded(int $countquotaexceeded): void
    {
        $this->countquotaexceeded = $countquotaexceeded;
    }

    /**
     * @return int
     */
    public function getCountconnectionrefused(): int
    {
        return $this->countconnectionrefused;
    }

    /**
     * @param int $countconnectionrefused
     */
    public function setCountconnectionrefused(int $countconnectionrefused): void
    {
        $this->countconnectionrefused = $countconnectionrefused;
    }

    /**
     * @return int
     */
    public function getCountheadererror(): int
    {
        return $this->countheadererror;
    }

    /**
     * @param int $countheadererror
     */
    public function setCountheadererror(int $countheadererror): void
    {
        $this->countheadererror = $countheadererror;
    }

    /**
     * @return int
     */
    public function getCountoutofoffice(): int
    {
        return $this->countoutofoffice;
    }

    /**
     * @param int $countoutofoffice
     */
    public function setCountoutofoffice(int $countoutofoffice): void
    {
        $this->countoutofoffice = $countoutofoffice;
    }

    /**
     * @return int
     */
    public function getCountfilterlist(): int
    {
        return $this->countfilterlist;
    }

    /**
     * @param int $countfilterlist
     */
    public function setCountfilterlist(int $countfilterlist): void
    {
        $this->countfilterlist = $countfilterlist;
    }

    /**
     * @return int
     */
    public function getCountmessagesize(): int
    {
        return $this->countmessagesize;
    }

    /**
     * @param int $countmessagesize
     */
    public function setCountmessagesize(int $countmessagesize): void
    {
        $this->countmessagesize = $countmessagesize;
    }

    /**
     * @return int
     */
    public function getCountpossiblespam(): int
    {
        return $this->countpossiblespam;
    }

    /**
     * @param int $countpossiblespam
     */
    public function setCountpossiblespam(int $countpossiblespam): void
    {
        $this->countpossiblespam = $countpossiblespam;
    }



    /**
     * @return int
     */
    public function getCountsum(): int
    {
        return $this->countunknownreason + $this->countnosenderfound + $this->countuserunknown + $this->countquotaexceeded + $this->countconnectionrefused + $this->countheadererror + $this->countoutofoffice + $this->countfilterlist + $this->countmessagesize + $this->countpossiblespam;
    }

    public function getRemoved(): int
    {
        return $this->removed;
    }

    public function setRemoved(int $removed): void
    {
        $this->removed = $removed;
    }

    public function isRemoved(): bool
    {
        return $this->removed > 0;
    }
}
