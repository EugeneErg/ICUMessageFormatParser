<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Message;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Format;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Skeleton;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class NumberTest extends TestCase
{
    #[Test]
    public function defaultNoOptions(): void
    {
        $n = Number::create('amount');
        $this->assertSame('{amount, number}', (string) $n);
    }

    #[Test]
    public function withSkeletonSuffix(): void
    {
        $n = Number::create('price', ['::', 'currency/EUR']);
        $this->assertSame('{price, number, ::currency/EUR}', (string) $n);
    }

    #[Test]
    public function withPatternCreatesSkeletonIfKnown(): void
    {
        // 'integer' is a known Format → becomes Skeleton
        $n = Number::create('n', [new Pattern('integer')]);
        $this->assertInstanceOf(Skeleton::class, $n->options);
        $this->assertSame(Format::Integer, $n->options->format);
    }

    #[Test]
    public function withUnknownPatternCreatesMessage(): void
    {
        $n = Number::create('n', [new Pattern('#,##0.00')]);
        $this->assertInstanceOf(Message::class, $n->options);
    }

    #[Test]
    public function getValue(): void
    {
        $this->assertSame('price', Number::create('price')->getValue());
    }

    #[Test]
    public function getAllVariables(): void
    {
        $this->assertSame(['amount'], Number::create('amount')->getAllVariables());
    }

    #[Test]
    public function getAllVariants(): void
    {
        $v = Number::create('n')->getAllVariants();
        $this->assertCount(1, $v);
    }

    #[Test]
    public function toStringWithSkeletonOutput(): void
    {
        $n = Number::create('price', ['::', 'percent', '.00']);
        $str = (string) $n;
        $this->assertStringContainsString('price, number,', $str);
    }

    #[Test]
    public function toStringWithEmptySkeleton(): void
    {
        // Default skeleton serialises to '' → no options shown
        $n = new Number('x', new Skeleton());
        $this->assertSame('{x, number}', (string) $n);
    }

    #[Test]
    public function withStringOptionCreatesMessage(): void
    {
        // string in options -> is_string path -> new Pattern($o) (line 73)
        $n = Number::create('n', ['custom format']);
        $this->assertSame('{n, number, custom format}', (string) $n);
    }
}
