<?php

declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use RSM\Rsmbouncemailprocessor\Controller\BouncemailController;
use RSM\Rsmbouncemailprocessor\Controller\RecipientreportController;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Regression tests for the backend module: it crashed with a Fluid exception because the delete link used
 * additionalAttributes="" (string instead of array), and could divide by zero for reports without data.
 */
#[CoversNothing]
final class BackendModuleRenderTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['scheduler'];

    protected array $testExtensionsToLoad = [
        'undkonsorten/taskqueue',
        'undkonsorten/typo3-cute-mailing',
        'ressourcenmangel/rsmbouncemailprocessor',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/BackendModule.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(\TYPO3\CMS\Core\Localization\LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER']);
    }

    public static function moduleActionDataProvider(): array
    {
        return [
            'bounce reports' => [BouncemailController::class, 'Bouncemail', 'list', 'newsletter with sendouts', 'bounces'],
            'recipient reports' => [RecipientreportController::class, 'Recipientreport', 'recipientlist', 'user1@example.test', 'recipients'],
        ];
    }

    #[Test]
    #[DataProvider('moduleActionDataProvider')]
    public function pageWithReportsRendersWithDeleteLink(string $controllerClass, string $controllerName, string $action, string $expectedContent, string $expectedTab): void
    {
        $response = $this->callAction($controllerClass, $controllerName, $action, 94);
        $content = (string)$response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsStringIgnoringCase($expectedContent, $content, 'content of ' . $action);
        // the link itself is only built with a real backend route, the markup after it proves the delete column was rendered
        self::assertStringContainsString('icon-actions-edit-delete', $content);
        self::assertStringNotContainsString('Please choose a report page first', $content);
        self::assertStringContainsString('data-active-tab="' . $expectedTab . '"', $content);
    }

    #[Test]
    public function reportsWithoutSendoutsAndProcessedMailsRenderWithoutDivisionByZero(): void
    {
        $response = $this->callAction(BouncemailController::class, 'Bouncemail', 'list', 94);

        // newsletter 2 has no sendouts and no processed mails, report 3 has no newsletter at all
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Newsletter without sendouts', (string)$response->getBody());
    }

    /**
     * 17 bounces for 3 sent mails were shown as "17 / 3 (566,7%)" and the progress bars exceeded 100 %.
     */
    #[Test]
    public function moreBouncesThanSentMailsAreCappedAtOneHundredPercent(): void
    {
        $content = (string)$this->callAction(BouncemailController::class, 'Bouncemail', 'list', 94)->getBody();

        self::assertStringNotContainsString('566,7', $content);
        self::assertStringContainsString('100,0 %', $content);
        self::assertStringContainsString('rsm-hint', $content, 'hint that there are more bounces than sent mails');
    }

    #[Test]
    public function bounceReportShowsSummaryAndOnlyReasonsThatOccurred(): void
    {
        $content = (string)$this->callAction(BouncemailController::class, 'Bouncemail', 'list', 94)->getBody();

        self::assertStringContainsString('rsm-summary', $content);
        self::assertStringContainsString('User unknown', $content);
        self::assertStringContainsString('Quota exceeded', $content);
        self::assertStringNotContainsString('Connection refused', $content);
        self::assertStringNotContainsString('Header error', $content);
    }

    #[Test]
    public function recipientReportShowsFilterCountAndReasons(): void
    {
        $content = (string)$this->callAction(RecipientreportController::class, 'Recipientreport', 'recipientlist', 94)->getBody();

        self::assertStringContainsString('rsm-filter', $content);
        self::assertStringContainsString('2 recipients', $content);
        self::assertStringContainsString('User unknown', $content);
        self::assertStringNotContainsString('Connection refused', $content);
    }

    #[Test]
    #[DataProvider('moduleActionDataProvider')]
    public function pageWithoutReportsForwardsToChoosePage(string $controllerClass, string $controllerName, string $action, string $unused, string $unusedTab): void
    {
        // page 1 has no reports in its rootline (the reports are on page 94)
        $response = $this->callAction($controllerClass, $controllerName, $action, 1);

        self::assertInstanceOf(ForwardResponse::class, $response);
        self::assertSame('choosePage', $response->getActionName());
    }

    #[Test]
    #[DataProvider('moduleActionDataProvider')]
    public function choosePageExplainsWhatToDo(string $controllerClass, string $controllerName, string $action, string $unused, string $expectedTab): void
    {
        $response = $this->callAction($controllerClass, $controllerName, 'choosePage', 1);
        $content = (string)$response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Please choose a report page first', $content);
        self::assertStringContainsString('Select that page', $content);
        self::assertStringContainsString('data-active-tab="' . $expectedTab . '"', $content);
    }

    private function callAction(string $controllerClass, string $controllerName, string $action, int $pageId): ResponseInterface
    {
        $module = $this->get(ModuleProvider::class)->getModule('web_rsmbouncemailprocessor', $GLOBALS['BE_USER']);
        $serverRequest = (new ServerRequest('https://localhost/typo3/module/web/rsmbouncemailprocessor?id=' . $pageId, 'GET'))
            ->withQueryParams(['id' => $pageId])
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('module', $module)
            ->withAttribute('route', (new Route($module->getPath(), ['packageName' => 'ressourcenmangel/rsmbouncemailprocessor']))->setOption('_identifier', $module->getIdentifier()))
            ->withAttribute('moduleData', new \TYPO3\CMS\Backend\Module\ModuleData($module->getIdentifier(), []));
        $serverRequest = $serverRequest->withAttribute(
            'normalizedParams',
            NormalizedParams::createFromRequest($serverRequest)
        );
        $extbaseParameters = new ExtbaseRequestParameters($controllerClass);
        $extbaseParameters->setControllerName($controllerName);
        $extbaseParameters->setControllerActionName($action);
        $extbaseParameters->setControllerExtensionName('Rsmbouncemailprocessor');
        $request = new Request($serverRequest->withAttribute('extbase', $extbaseParameters));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return $this->get($controllerClass)->processRequest($request);
    }
}
