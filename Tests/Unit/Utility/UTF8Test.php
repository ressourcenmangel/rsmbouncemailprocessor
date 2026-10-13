<?php

declare(strict_types=1);

namespace RSM\Rsmbouncemailprocessor\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RSM\Rsmbouncemailprocessor\Utility\UTF8;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[CoversClass(UTF8::class)]
final class UTF8Test extends UnitTestCase
{
    #[Test]
    public function fixReturnsValidUtf8Unchanged(): void
    {
        self::assertSame('valid utf8 äöü', UTF8::fix('valid utf8 äöü'));
    }

    #[Test]
    public function fixReturnsNonStringValueUnchanged(): void
    {
        self::assertNull(UTF8::fix(null));
    }
}
