<?php
namespace RSM\Rsmbouncemailprocessor\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use Undkonsorten\CuteMailing\Domain\Model\Newsletter;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;

class Bouncereport extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var Newsletter
     * @Lazy
     */
    protected $newsletterid;

    /**
     * @var \DateTime
     */
    protected $timeprocessed;

    /**
     * @var int
     */
    protected $countmails;

    /**
     * @var int
     */
    protected $countprocessed;

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
     * @return Newsletter|null
     */
    public function getNewsletterid(): Newsletter|null
    {
        if ($this->newsletterid instanceof LazyLoadingProxy) {
            $this->newsletterid->_loadRealInstance();
        }

        return $this->newsletterid;
    }

    /**
     * @param Newsletter $newsletterid
     */
    public function setNewsletterid(Newsletter $newsletterid): void
    {
        $this->newsletterid = $newsletterid;
    }



    /**
     * @return \DateTime
     */
    public function getTimeprocessed(): \DateTime
    {
        return $this->timeprocessed;
    }

    /**
     * @param \DateTime $timeprocessed
     */
    public function setTimeprocessed(\DateTime $timeprocessed): void
    {
        $this->timeprocessed = $timeprocessed;
    }

    /**
     * @return int
     */
    public function getCountmails(): int
    {
        return $this->countmails;
    }

    /**
     * @param int $countmails
     */
    public function setCountmails(int $countmails): void
    {
        $this->countmails = $countmails;
    }

    /**
     * @return int
     */
    public function getCountprocessed(): int
    {
        return $this->countprocessed;
    }

    /**
     * @param int $countprocessed
     */
    public function setCountprocessed(int $countprocessed): void
    {
        $this->countprocessed = $countprocessed;
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


}
