<?php

declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Tests\Functional\Resources;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversNothing]
final class BackendTemplatesFluidSyntaxTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'undkonsorten/taskqueue',
        'undkonsorten/typo3-cute-mailing',
        'ressourcenmangel/rsmbouncemailprocessor',
    ];

    public static function backendTemplateFileDataProvider(): array
    {
        $templatesPath = dirname(__DIR__, 3) . '/Resources/Private/Templates/';
        $data = [];
        foreach (glob($templatesPath . '*.html') as $templateFile) {
            $data[basename($templateFile)] = [$templateFile];
        }
        return $data;
    }

    /**
     * Regression test: a leftover `xmlns:rsmbouncemailprocessor="http://typo3.org/ns/RSM\Rsmbouncemailprocessor\ViewHelpers"`
     * on the root <html> tag of these templates used a PHP namespace directly as a Fluid xmlns value, which
     * Fluid rejects since it requires namespaces to start with "http://typo3.org/ns/" and not contain a raw
     * PHP namespace path. This crashed every backend module action ("list", "recipientlist", ...) with a
     * Fluid parse error as soon as the module was opened.
     */
    #[Test]
    #[DataProvider('backendTemplateFileDataProvider')]
    public function backendTemplateParsesWithoutError(string $templateFile): void
    {
        $renderingContextFactory = $this->get(RenderingContextFactory::class);
        $renderingContext = $renderingContextFactory->create();
        $templateSource = file_get_contents($templateFile);

        $renderingContext->getTemplateParser()->parse($templateSource, $templateFile);

        self::assertStringNotContainsString(
            'http://typo3.org/ns/RSM',
            $templateSource,
            'Template must not declare a Fluid namespace using a PHP namespace path.',
        );
    }
}
