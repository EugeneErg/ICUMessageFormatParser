<?php
declare(strict_types = 1);
namespace Tests\DataTransferObjects;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\SelectOrdinal;
use PHPUnit\Framework\TestCase;

final class SelectOrdinalTest extends TestCase
{
    public function testGetName(): void { self::assertSame('selectordinal', SelectOrdinal::getName()); }

    public function testToString(): void
    {
        $s = SelectOrdinal::create('place', [
            'one'   => [new Pattern('1st')],
            'two'   => [new Pattern('2nd')],
            'few'   => [new Pattern('3rd')],
            'other' => [new Pattern('#th')],
        ]);
        $str = (string) $s;
        self::assertStringContainsString('{place, selectordinal,', $str);
        self::assertStringContainsString('one {1st}', $str);
        self::assertStringContainsString('other {#th}', $str);
    }

    public function testGetAllVariantsMultipleOptions(): void
    {
        $s = SelectOrdinal::create('n', [
            'one'   => [new Pattern('st')],
            'other' => [new Pattern('th')],
        ]);
        $variants = $s->getAllVariants();
        self::assertCount(2, $variants);
    }

    public function testNumericEquality(): void
    {
        $s = SelectOrdinal::create('n', [
            '=1'    => [new Pattern('first')],
            '=2'    => [new Pattern('second')],
            'other' => [new Pattern('other')],
        ]);
        $str = (string) $s;
        self::assertStringContainsString('=1 {first}', $str);
        self::assertStringContainsString('=2 {second}', $str);
    }
}
