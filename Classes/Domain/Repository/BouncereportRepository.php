<?php

declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class BouncereportRepository extends Repository
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
    public function findByRootline(array $rootline)
    {
        $storagePageIds = [];
        foreach ($rootline as $key => $value) {
            $storagePageIds[] = $value['uid'];
        }
        $query = $this->createQuery();
        $defaultSettings = $query->getQuerySettings();
        $defaultSettings->setRespectStoragePage(true);
        $defaultSettings->setStoragePageIds($storagePageIds);
        $query->setQuerySettings($defaultSettings);
        $query->setOrderings(['crdate' => QueryInterface::ORDER_DESCENDING]);
        return $query->execute();
    }
}
