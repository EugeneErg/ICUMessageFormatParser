<?php
declare(strict_types = 1);
namespace Tests\DataTransferObjects;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use PHPUnit\Framework\TestCase;

final class PatternTest extends TestCase
{
    public function testToString(): void { self::assertSame('Hello', (string) new Pattern('Hello')); }
    public function testToStringEscapesQuotes(): void { self::assertSame("it''s", (string) new Pattern("it's")); }
    public function testCreate(): void { self::assertSame('test', (new Pattern('test'))->value); }
    public function testGetAllVariants(): void {
        $p = new Pattern('hi');
        $v = $p->getAllVariants();
        self::assertCount(1, $v);
        self::assertSame('hi', (string) $v[0]->types);
    }
    public function testGetAllVariables(): void { self::assertSame([], (new Pattern('text'))->getAllVariables()); }
    public function testMergeWithPattern(): void {
        $a = new Pattern('Hello ');
        $b = new Pattern('World');
        $merged = $a->merge($b);
        self::assertCount(1, $merged);
        self::assertSame('Hello World', $merged[0]->value);
    }
    public function testMergeWithText(): void {
        // merge accepts any ICUTypeInterface but Text is different class
        $a = new Pattern('Hello');
        $b = new \EugeneErg\ICUMessageFormatParser\DataTransferObjects\Text(' World');
        $merged = $a->merge($b);
        self::assertSame('Hello World', $merged[0]->value);
    }
}
