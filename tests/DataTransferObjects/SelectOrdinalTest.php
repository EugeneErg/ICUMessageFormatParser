<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\SelectOrdinal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SelectOrdinalTest extends TestCase
{
    #[Test]
    public function getName(): void
    {
        $this->assertSame('selectordinal', SelectOrdinal::getName());
    }

    #[Test]
    public function toString(): void
    {
        $s = SelectOrdinal::create('place', [
            'one' => [new Pattern('1st')],
            'two' => [new Pattern('2nd')],
            'few' => [new Pattern('3rd')],
            'other' => [new Pattern('#th')],
        ]);
        $str = (string) $s;
        $this->assertStringContainsString('{place, selectordinal,', $str);
        $this->assertStringContainsString('one {1st}', $str);
        $this->assertStringContainsString('other {#th}', $str);
    }

    #[Test]
    public function getAllVariantsMultipleOptions(): void
    {
        $s = SelectOrdinal::create('n', [
            'one' => [new Pattern('st')],
            'other' => [new Pattern('th')],
        ]);
        $variants = $s->getAllVariants();
        $this->assertCount(2, $variants);
    }

    #[Test]
    public function numericEquality(): void
    {
        $s = SelectOrdinal::create('n', [
            '=1' => [new Pattern('first')],
            '=2' => [new Pattern('second')],
            'other' => [new Pattern('other')],
        ]);
        $str = (string) $s;
        $this->assertStringContainsString('=1 {first}', $str);
        $this->assertStringContainsString('=2 {second}', $str);
    }

    // -----------------------------------------------------------------------
    // offset support
    // -----------------------------------------------------------------------

    #[Test]
    public function selectOrdinalOffsetDefault(): void
    {
        $s = SelectOrdinal::create('n', ['one' => [new Pattern('#st')], 'other' => [new Pattern('#th')]]);
        $this->assertSame(0, $s->offset);
    }

    #[Test]
    public function selectOrdinalOffsetSerialisation(): void
    {
        $s = SelectOrdinal::create('n', [
            'offset' => 1,
            'one' => [new Pattern('#st')],
            'other' => [new Pattern('#th')],
        ]);
        $str = (string) $s;
        $this->assertStringContainsString('offset:1', $str);
        $this->assertMatchesRegularExpression('/\\{n, selectordinal, offset:1 /', $str);
    }

    #[Test]
    public function selectOrdinalOffsetZeroNotSerialisedExplicitly(): void
    {
        $s = SelectOrdinal::create('n', ['other' => [new Pattern('#th')]]);
        $this->assertStringNotContainsString('offset:', (string) $s);
    }

    #[Test]
    public function selectOrdinalOffsetPreservedInReplaceRecursive(): void
    {
        $s = SelectOrdinal::create('n', ['offset' => 2, 'other' => [new Pattern('#th')]]);
        $replaced = $s->replaceRecursive([]);
        $this->assertSame(2, $replaced->offset);
    }
}
