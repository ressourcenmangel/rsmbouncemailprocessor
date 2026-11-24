<?php

declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Task;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Services for scheduler tasks, fetched on demand instead of constructor injection:
 * TYPO3 13 unserializes tasks (no constructor call), TYPO3 14 only uses the container for public
 * services, and tasks must not carry services when they are stored.
 */
trait LazyServicesTrait
{
    private function connectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }

    private function persistenceManager(): PersistenceManager
    {
        return GeneralUtility::makeInstance(PersistenceManager::class);
    }

    /**
     * Scheduler runs from the CLI have no request, but the ConfigurationManager needs one to build
     * the (global) TypoScript. TYPO3 13 guesses the page without a page id, TYPO3 14 does not and
     * would load only the global TypoScript. So the first root page is passed as page id (see
     * firstRootPageId()). The TypoScript of that page is used (rootline templates, site sets), plus the TypoScript every extension registers
     * globally with addTypoScript() (that is how this extension is configured). With several
     * sites only the first site is read.
     */
    private function configurationManager(): ConfigurationManager
    {
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        if (!($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && method_exists($configurationManager, 'setRequest')
        ) {
            $configurationManager->setRequest(
                (new ServerRequest())
                    ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
                    ->withQueryParams(['id' => $this->firstRootPageId()])
            );
        }
        return $configurationManager;
    }

    /**
     * The first page with "Use as root page" in the page tree, 0 if there is none.
     */
    private function firstRootPageId(): int
    {
        $queryBuilder = $this->connectionPool()->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        return (int)$queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('is_siteroot', 1),
                $queryBuilder->expr()->eq('sys_language_uid', 0),
                $queryBuilder->expr()->eq('t3ver_wsid', 0),
            )
            ->orderBy('sorting')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Tasks need the TypoScript of the extension, without it they would silently do nothing.
     */
    private function missingConfiguration(string $setting): \RuntimeException
    {
        return new \RuntimeException(
            'Configuration missing: module.tx_rsmbouncemailprocessor.' . $setting . ' was not found. '
            . 'The extension registers its TypoScript globally, so it is probably overridden or removed by the TypoScript of the first root page '
            . '(template or site set). The scheduler reads the first page with "Use as root page".',
            1763900002
        );
    }
}
