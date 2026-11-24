<?php

declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Tests\Functional\Task;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RSM\Rsmbouncemailprocessor\Task\AnalyzeBounceMail;
use RSM\Rsmbouncemailprocessor\Task\AnalyzeBounceMailAdditionalFields;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(AnalyzeBounceMailAdditionalFields::class)]
final class AnalyzeBounceMailAdditionalFieldsTest extends FunctionalTestCase
{
    private const FIELDS = ['server', 'port', 'user', 'password', 'service', 'maxProcessed', 'deletealways'];

    protected array $coreExtensionsToLoad = ['scheduler'];

    protected array $testExtensionsToLoad = [
        'undkonsorten/taskqueue',
        'undkonsorten/typo3-cute-mailing',
        'ressourcenmangel/rsmbouncemailprocessor',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    /**
     * Regression test: the constructor used LanguageService::includeLLFile(), which does not exist in
     * TYPO3 13/14, so adding the "RSM analyze bounce mail" task in the scheduler module crashed.
     */
    #[Test]
    public function providerCanBeInstantiated(): void
    {
        self::assertInstanceOf(
            AnalyzeBounceMailAdditionalFields::class,
            new AnalyzeBounceMailAdditionalFields()
        );
    }

    #[Test]
    public function additionalFieldsForNewTaskAreTranslated(): void
    {
        $taskInfo = [];
        $fields = (new AnalyzeBounceMailAdditionalFields())->getAdditionalFields($taskInfo, null, $this->schedulerModule());

        self::assertSame(self::FIELDS, array_keys($fields));
        foreach ($fields as $name => $field) {
            self::assertNotSame('', $field['code'], $name);
            self::assertStringStartsNotWith('scheduler.rsmbouncemail', $field['label'], 'label of ' . $name . ' is not translated');
            self::assertStringStartsNotWith('scheduler.rsmbouncemail', $field['cshLabel'], 'csh label of ' . $name . ' is not translated');
        }
    }

    #[Test]
    public function additionalFieldsForExistingTaskContainTaskValues(): void
    {
        $task = $this->get(AnalyzeBounceMail::class);
        $task->setServer('imap.example.test');
        $task->setPort(993);
        $task->setUser('bounce');
        $task->setPassword('secret');
        $task->setService('pop3');
        $task->setMaxProcessed(25);
        $task->setDeletealways(true);

        $taskInfo = [];
        $fields = (new AnalyzeBounceMailAdditionalFields())->getAdditionalFields($taskInfo, $task, $this->schedulerModule());

        self::assertStringContainsString('value="imap.example.test"', $fields['server']['code']);
        self::assertStringContainsString('value="993"', $fields['port']['code']);
        self::assertStringContainsString('value="25"', $fields['maxProcessed']['code']);
        self::assertMatchesRegularExpression('/<option value="pop3"\s+selected="selected"/', $fields['service']['code']);
        self::assertStringContainsString('checked="checked"', $fields['deletealways']['code']);
    }

    /**
     * Values end up in value="..." attributes of the scheduler form, a quote or a tag in the server
     * name must not break out of it (XSS for backend users who edit the task).
     */
    #[Test]
    public function taskValuesAreEscapedInTheForm(): void
    {
        $task = $this->get(AnalyzeBounceMail::class);
        $task->setServer('"><script>alert(1)</script>');
        $task->setUser('a"b\'c');

        $taskInfo = [];
        $fields = (new AnalyzeBounceMailAdditionalFields())->getAdditionalFields($taskInfo, $task, $this->schedulerModule());

        self::assertStringNotContainsString('<script>', $fields['server']['code']);
        self::assertStringContainsString('value="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $fields['server']['code']);
        self::assertStringNotContainsString('a"b', $fields['user']['code']);
    }

    /**
     * The password is never sent back to the browser.
     */
    #[Test]
    public function passwordIsNeverPrefilledInTheForm(): void
    {
        $task = $this->get(AnalyzeBounceMail::class);
        $task->setPassword('s3cr3t"pw');

        $taskInfo = [];
        $fields = (new AnalyzeBounceMailAdditionalFields())->getAdditionalFields($taskInfo, $task, $this->schedulerModule());

        self::assertStringNotContainsString('s3cr3t', $fields['password']['code']);
        self::assertStringContainsString('value=""', $fields['password']['code']);
    }

    #[Test]
    public function emptyPasswordInputKeepsTheStoredPassword(): void
    {
        $task = $this->get(AnalyzeBounceMail::class);
        $task->setPassword('stored');
        $data = ['bounceServer' => 'imap', 'bouncePort' => '143', 'bounceUser' => 'bounce', 'bounceService' => 'imap', 'bounceProcessed' => '50'];

        (new AnalyzeBounceMailAdditionalFields())->saveAdditionalFields($data + ['bouncePassword' => ''], $task);
        self::assertSame('stored', $task->getPassword());

        (new AnalyzeBounceMailAdditionalFields())->saveAdditionalFields($data + ['bouncePassword' => 'new'], $task);
        self::assertSame('new', $task->getPassword());
    }

    #[Test]
    public function submittedDataIsSavedInTask(): void
    {
        $task = $this->get(AnalyzeBounceMail::class);

        (new AnalyzeBounceMailAdditionalFields())->saveAdditionalFields([
            'bounceServer' => 'imap',
            'bouncePort' => '143',
            'bounceUser' => 'bounce',
            'bouncePassword' => 'bounce',
            'bounceService' => 'imap',
            'bounceProcessed' => '50',
        ], $task);

        self::assertSame('imap', $task->getServer());
        self::assertSame(143, $task->getPort());
        self::assertSame(50, $task->getMaxProcessed());
        self::assertFalse($task->getDeletealways());
    }

    #[Test]
    public function validationFailsForUnreachableServerWithoutException(): void
    {
        if (!extension_loaded('imap')) {
            self::markTestSkipped('PHP extension imap is not loaded, covered by validationFailsWithoutImapExtension');
        }
        $submittedData = [
            'bounceServer' => 'imap.invalid',
            'bouncePort' => '143',
            'bounceUser' => 'nobody',
            'bouncePassword' => 'nothing',
            'bounceService' => 'imap',
        ];

        self::assertFalse(
            (new AnalyzeBounceMailAdditionalFields())->validateAdditionalFields($submittedData, $this->schedulerModule())
        );
    }

    #[Test]
    public function validationFailsWithoutImapExtension(): void
    {
        if (extension_loaded('imap')) {
            self::markTestSkipped('PHP extension imap is loaded, covered by validationFailsForUnreachableServerWithoutException');
        }
        $submittedData = [];

        self::assertFalse(
            (new AnalyzeBounceMailAdditionalFields())->validateAdditionalFields($submittedData, $this->schedulerModule())
        );
    }

    /**
     * Guards against label keys used in code that are missing in the language file.
     */
    #[Test]
    public function allUsedLabelKeysExistInLanguageFile(): void
    {
        $xlf = file_get_contents(dirname(__DIR__, 3) . '/Resources/Private/Language/locallang_mod.xlf');
        $keys = ['scheduler.rsmbouncemail.dataVerification', 'scheduler.rsmbouncemail.phpImapError'];
        foreach (self::FIELDS as $field) {
            $keys[] = 'scheduler.rsmbouncemail.' . $field;
            $keys[] = 'scheduler.rsmbouncemailcsh.' . $field;
        }
        foreach ($keys as $key) {
            self::assertStringContainsString('resname="' . $key . '"', $xlf, $key);
        }
    }

    private function schedulerModule(): SchedulerModuleController
    {
        // final class that is only passed through, so it is created without its dependencies
        return (new \ReflectionClass(SchedulerModuleController::class))->newInstanceWithoutConstructor();
    }
}
