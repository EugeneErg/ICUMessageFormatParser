<?php
declare(strict_types = 1);
namespace Tests\DataTransferObjects;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Text;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Types;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variant;
use PHPUnit\Framework\TestCase;

final class VariantTest extends TestCase
{
    public function testDefaultTypes(): void {
        $v = new Variant();
        self::assertSame('', (string) $v->types);
        self::assertSame([], $v->cases);
    }
    public function testMergeEmptyVariants(): void {
        $a = new Variant(new Types([new Pattern('Hello')]));
        $b = new Variant(new Types([new Pattern(' World')]));
        $merged = $a->merge($b);
        self::assertNotNull($merged);
        self::assertSame('Hello World', (string) $merged->types);
    }
    public function testMergeAdjacentSameTypesMerge(): void {
        // Two Pattern nodes should merge into one
        $a = new Variant(new Types([new Pattern('A')]));
        $b = new Variant(new Types([new Pattern('B')]));
        $m = $a->merge($b);
        self::assertCount(1, $m->types->types); // merged into single Pattern
        self::assertSame('AB', (string) $m->types);
    }
    public function testMergeDifferentTypesNoMerge(): void {
        $a = new Variant(new Types([new Pattern('A')]));
        $b = new Variant(new Types([new Text('B')]));
        $m = $a->merge($b);
        self::assertNotNull($m);
        self::assertCount(2, $m->types->types);
    }
    public function testMergeWithCompatibleCases(): void {
        $a = new Variant(cases: ['select' => ['gender' => 'male']]);
        $b = new Variant();
        $m = $a->merge($b);
        self::assertNotNull($m);
        self::assertSame('male', $m->cases['select']['gender']);
    }
    public function testMergeWithIncompatibleCasesReturnsNull(): void {
        $a = new Variant(cases: ['select' => ['gender' => 'male']]);
        $b = new Variant(cases: ['select' => ['gender' => 'female']]);
        self::assertNull($a->merge($b));
    }
    public function testMergeWithLeftEmpty(): void {
        $a = new Variant(new Types([]));
        $b = new Variant(new Types([new Pattern('X')]));
        $m = $a->merge($b);
        self::assertSame('X', (string) $m->types);
    }
}
