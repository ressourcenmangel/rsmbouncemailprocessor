<?php

declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Task;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
     * the (global) TypoScript. Without a page id, Extbase guesses the page: the first page with
     * "is_siteroot", else the page of the first root sys_template, else 0. The TypoScript of that
     * page is used (rootline templates, site sets), plus the TypoScript every extension registers
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
                (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            );
        }
        return $configurationManager;
    }

    /**
     * Tasks need the TypoScript of the extension, without it they would silently do nothing.
     */
    private function missingConfiguration(string $setting): \RuntimeException
    {
        return new \RuntimeException(
            'Configuration missing: module.tx_rsmbouncemailprocessor.' . $setting . ' was not found. '
            . 'Include the TypoScript of the extension rsmbouncemailprocessor on the root page of the (first) site.',
            1763900002
        );
    }
}
