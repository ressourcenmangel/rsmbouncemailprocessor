<?php

declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Tests\Functional\Task;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RSM\Rsmbouncemailprocessor\Task\AnalyzeBounceMail;
use RSM\Rsmbouncemailprocessor\Task\CleanTaskQueue;
use RSM\Rsmbouncemailprocessor\Task\ProcessBounceMail;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversNothing]
final class SchedulerTasksTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['scheduler'];

    protected array $testExtensionsToLoad = [
        'undkonsorten/taskqueue',
        'undkonsorten/typo3-cute-mailing',
        'ressourcenmangel/rsmbouncemailprocessor',
    ];

    public static function taskClassDataProvider(): array
    {
        return [
            'analyze bounce mail' => [AnalyzeBounceMail::class],
            'process bounce mail' => [ProcessBounceMail::class],
            'clean task queue' => [CleanTaskQueue::class],
        ];
    }

    #[Test]
    #[DataProvider('taskClassDataProvider')]
    public function taskIsRegisteredInScheduler(string $taskClass): void
    {
        $registration = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$taskClass] ?? null;

        self::assertIsArray($registration);
        self::assertNotEmpty($registration['title']);
        self::assertNotEmpty($registration['description']);
    }

    #[Test]
    #[DataProvider('taskClassDataProvider')]
    public function taskCanBeInstantiated(string $taskClass): void
    {
        self::assertInstanceOf(AbstractTask::class, $this->get($taskClass));
    }

    /**
     * The task parameters are stored as JSON in the scheduler task record, so they must neither
     * contain services (persistence manager, repositories, ...) nor fail to encode.
     */
    #[Test]
    #[DataProvider('taskClassDataProvider')]
    public function taskParametersAreStorable(string $taskClass): void
    {
        $this->skipWithoutTaskParameters();
        $parameters = $this->get($taskClass)->getTaskParameters();

        foreach ($parameters as $name => $value) {
            self::assertFalse(is_object($value), 'parameter "' . $name . '" is an object');
        }
        self::assertNotFalse(json_encode($parameters, JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function analyzeBounceMailParametersSurviveRoundTrip(): void
    {
        $this->skipWithoutTaskParameters();
        $task = $this->get(AnalyzeBounceMail::class);
        $task->setTaskParameters([
            'server' => 'imap',
            'port' => 143,
            'user' => 'bounce',
            'password' => 'bounce',
            'service' => 'imap',
            'maxProcessed' => 50,
            'deletealways' => true,
        ]);

        $stored = json_decode(json_encode($task->getTaskParameters(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $restored = $this->get(AnalyzeBounceMail::class);
        $restored->setTaskParameters($stored);

        self::assertSame('imap', $restored->getServer());
        self::assertSame(143, $restored->getPort());
        self::assertSame('bounce', $restored->getUser());
        self::assertSame(50, $restored->getMaxProcessed());
        self::assertTrue($restored->getDeletealways());
    }

    /**
     * TYPO3 13 stores the serialized task object, so a stored task must neither contain services
     * nor lose its settings. (TYPO3 14 stores parameters, see taskParametersAreStorable.)
     */
    #[Test]
    #[DataProvider('taskClassDataProvider')]
    public function serializedTaskContainsNoServices(string $taskClass): void
    {
        $task = $this->get($taskClass);
        if ($task instanceof AnalyzeBounceMail) {
            $task->setServer('imap');
            $task->setPort(143);
            $task->setMaxProcessed(50);
        }

        // the TYPO3 13 scheduler removes the logger before storing a task
        if (method_exists($task, 'unsetScheduler')) {
            $task->unsetScheduler();
        }
        $serialized = serialize($task);

        self::assertStringNotContainsString('ConnectionPool', $serialized);
        self::assertStringNotContainsString('PersistenceManager', $serialized);
        self::assertStringNotContainsString('ConfigurationManager', $serialized);
        $restored = unserialize($serialized);
        self::assertInstanceOf($taskClass, $restored);
        if ($restored instanceof AnalyzeBounceMail) {
            self::assertSame('imap', $restored->getServer());
            self::assertSame(50, $restored->getMaxProcessed());
        }
    }

    #[Test]
    public function cleanTaskQueueRunsOnEmptyDatabase(): void
    {
        self::assertTrue($this->get(CleanTaskQueue::class)->execute());
    }

    #[Test]
    public function processBounceMailRunsOnEmptyDatabase(): void
    {
        self::assertTrue($this->get(ProcessBounceMail::class)->execute());
    }

    private function skipWithoutTaskParameters(): void
    {
        if (!method_exists(AbstractTask::class, 'getTaskParameters')) {
            self::markTestSkipped('Task parameters exist since TYPO3 14, TYPO3 13 stores serialized task objects');
        }
    }
}
