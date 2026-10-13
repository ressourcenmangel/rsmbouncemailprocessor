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
     * the (global) TypoScript. Without a page id this is the TypoScript of the installed extensions.
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
}
