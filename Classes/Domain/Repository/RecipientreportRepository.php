<?php

declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class RecipientreportRepository extends Repository
{
    public function initializeObject() {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\Extbase\\Object\\ObjectManager');
        $querySettings = $objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
        $querySettings->setRespectStoragePage(FALSE);
        $this->setDefaultQuerySettings($querySettings);
    }


    /**
     * @return array|object[]|QueryResultInterface
     */
    public function findByRootline(array $rootline, string $searchstring = '', int $searchamount = 0)
    {
        $storagePageIds = [];
        foreach ($rootline as $key => $value) {
            $storagePageIds[] = $value['uid'];
        }
        $query = $this->createQuery();
        $defaultSettings = $query->getQuerySettings();
        $defaultSettings->setRespectStoragePage(true);
        $defaultSettings->setStoragePageIds($storagePageIds);


        $constraints = [];
        if($searchstring) {
            $constraints[] = $query->like('email', "%$searchstring%");
        }
        if($searchamount) {
            $orconstraints[] = $query->greaterThanOrEqual('countunknownreason', $searchamount);
            $orconstraints[] = $query->greaterThanOrEqual('countnosenderfound', $searchamount);
            $orconstraints[] = $query->greaterThanOrEqual('countuserunknown', $searchamount);
            $orconstraints[] = $query->greaterThanOrEqual('countquotaexceeded', $searchamount);
            $orconstraints[] = $query->greaterThanOrEqual('countconnectionrefused', $searchamount);
            $orconstraints[] = $query->greaterThanOrEqual('countheadererror', $searchamount);
            $orconstraints[] = $query->greaterThanOrEqual('countoutofoffice', $searchamount);
            $orconstraints[] = $query->greaterThanOrEqual('countfilterlist', $searchamount);
            $orconstraints[] = $query->greaterThanOrEqual('countmessagesize', $searchamount);
            $orconstraints[] = $query->greaterThanOrEqual('countpossiblespam', $searchamount);
            $constraints[] =$query->logicalOr($orconstraints);
        }

        if(count($constraints)){
            $query->matching(
                $query->logicalAnd($constraints),
            );
        }

        $query->setQuerySettings($defaultSettings);
        $query->setOrderings(['crdate' => QueryInterface::ORDER_DESCENDING]);

        return $query->execute();
    }
}
