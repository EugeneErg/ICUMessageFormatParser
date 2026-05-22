<?php
declare(strict_types = 1);
namespace Tests\DataTransferObjects;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Message;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Format;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Skeleton;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use PHPUnit\Framework\TestCase;

final class NumberTest extends TestCase
{
    public function testDefaultNoOptions(): void
    {
        $n = Number::create('amount');
        self::assertSame('{amount, number}', (string) $n);
    }

    public function testWithSkeletonSuffix(): void
    {
        $n = Number::create('price', ['::', 'currency/EUR']);
        self::assertSame('{price, number, currency/EUR}', (string) $n);
    }

    public function testWithPatternCreatesSkeletonIfKnown(): void
    {
        // 'integer' is a known Format → becomes Skeleton
        $n = Number::create('n', [new Pattern('integer')]);
        self::assertInstanceOf(Skeleton::class, $n->options);
        self::assertSame(Format::Integer, $n->options->format);
    }

    public function testWithUnknownPatternCreatesMessage(): void
    {
        $n = Number::create('n', [new Pattern('#,##0.00')]);
        self::assertInstanceOf(Message::class, $n->options);
    }

    public function testGetValue(): void { self::assertSame('price', Number::create('price')->getValue()); }

    public function testGetAllVariables(): void
    {
        self::assertSame(['amount'], Number::create('amount')->getAllVariables());
    }

    public function testGetAllVariants(): void
    {
        $v = Number::create('n')->getAllVariants();
        self::assertCount(1, $v);
    }

    public function testToStringWithSkeletonOutput(): void
    {
        $n = Number::create('price', ['::', 'percent', '.00']);
        $str = (string) $n;
        self::assertStringContainsString('price, number,', $str);
    }

    public function testToStringWithEmptySkeleton(): void
    {
        // Default skeleton serialises to '' → no options shown
        $n = new Number('x', new Skeleton());
        self::assertSame('{x, number}', (string) $n);
    }
}
