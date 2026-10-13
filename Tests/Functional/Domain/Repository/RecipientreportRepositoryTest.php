<?php

declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RSM\Rsmbouncemailprocessor\Domain\Repository\RecipientreportRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(RecipientreportRepository::class)]
final class RecipientreportRepositoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'undkonsorten/taskqueue',
        'undkonsorten/typo3-cute-mailing',
        'ressourcenmangel/rsmbouncemailprocessor',
    ];

    #[Test]
    public function findByRootlineReturnsEmptyResultWithoutFatalError(): void
    {
        $repository = $this->get(RecipientreportRepository::class);

        $result = $repository->findByRootline([['uid' => 1]]);

        self::assertCount(0, $result);
    }
}
