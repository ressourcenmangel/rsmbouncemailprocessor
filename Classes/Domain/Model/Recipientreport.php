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
    protected $countunknownreason;

    /**
     * @var int
     */
    protected $countnosenderfound;

    /**
     * @var int
     */
    protected $countuserunknown;

    /**
     * @var int
     */
    protected $countquotaexceeded;

    /**
     * @var int
     */
    protected $countconnectionrefused;

    /**
     * @var int
     */
    protected $countheadererror;

    /**
     * @var int
     */
    protected $countoutofoffice;

    /**
     * @var int
     */
    protected $countfilterlist;

    /**
     * @var int
     */
    protected $countmessagesize;

    /**
     * @var int
     */
    protected $countpossiblespam;


    /**
     * @var int
     */
    protected $countsum;


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
        return $this->countpossiblespam + $this->countunknownreason + $this->countnosenderfound + $this->countuserunknown + $this->countquotaexceeded + $this->countconnectionrefused + $this->countheadererror + $this->countoutofoffice + $this->countfilterlist + $this->countmessagesize + $this->countpossiblespam;
    }


}
