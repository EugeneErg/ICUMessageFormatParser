<?php
declare(strict_types = 1);
namespace Tests\DataTransferObjects;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Text;
use PHPUnit\Framework\TestCase;

final class TextTest extends TestCase
{
    public function testToStringWrapsInQuotes(): void { self::assertSame("'Hello'", (string) new Text('Hello')); }
    public function testToStringEscapesSingleQuotes(): void { self::assertSame("'it''s'", (string) new Text("it's")); }
    public function testGetAllVariables(): void { self::assertSame([], (new Text('foo'))->getAllVariables()); }
    public function testMerge(): void {
        $a = new Text('Hello ');
        $b = new Text('World');
        $merged = $a->merge($b);
        self::assertCount(1, $merged);
        self::assertSame('Hello World', $merged[0]->value);
    }
    public function testGetAllVariants(): void {
        $t = new Text('hi');
        $v = $t->getAllVariants();
        self::assertCount(1, $v);
    }
}
