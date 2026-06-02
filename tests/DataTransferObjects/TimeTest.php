<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Time;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class TimeTest extends TestCase
{
    #[Test]
    public function getAllVariantsReturnsSelf(): void
    {
        $t = new Time('t');
        $variants = $t->getAllVariants();
        $this->assertCount(1, $variants);
    }

    #[Test]
    public function getAllVariablesReturnsValue(): void
    {
        $t = new Time('t');
        $this->assertSame(['t'], $t->getAllVariables());
    }
}
