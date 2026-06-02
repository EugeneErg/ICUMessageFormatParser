<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Text;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Types;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variant;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class VariantTest extends TestCase
{
    #[Test]
    public function defaultTypes(): void
    {
        $v = new Variant();
        $this->assertSame('', (string) $v->types);
        $this->assertSame([], $v->cases);
    }

    #[Test]
    public function mergeEmptyVariants(): void
    {
        $a = new Variant(new Types([new Pattern('Hello')]));
        $b = new Variant(new Types([new Pattern(' World')]));
        $merged = $a->merge($b);
        $this->assertNotNull($merged);
        $this->assertSame('Hello World', (string) $merged->types);
    }

    #[Test]
    public function mergeAdjacentSameTypesMerge(): void
    {
        // Two Pattern nodes should merge into one
        $a = new Variant(new Types([new Pattern('A')]));
        $b = new Variant(new Types([new Pattern('B')]));

        /** @var Variant $m */
        $m = $a->merge($b);
        $this->assertCount(1, $m->types->types); // merged into single Pattern
        $this->assertSame('AB', (string) $m->types);
    }

    #[Test]
    public function mergeDifferentTypesNoMerge(): void
    {
        $a = new Variant(new Types([new Pattern('A')]));
        $b = new Variant(new Types([new Text('B')]));
        $m = $a->merge($b);
        $this->assertNotNull($m);
        $this->assertCount(2, $m->types->types);
    }

    #[Test]
    public function mergeWithCompatibleCases(): void
    {
        $a = new Variant(cases: ['select' => ['gender' => 'male']]);
        $b = new Variant();
        $m = $a->merge($b);
        $this->assertNotNull($m);
        $this->assertSame('male', $m->cases['select']['gender']);
    }

    #[Test]
    public function mergeWithIncompatibleCasesReturnsNull(): void
    {
        $a = new Variant(cases: ['select' => ['gender' => 'male']]);
        $b = new Variant(cases: ['select' => ['gender' => 'female']]);
        $this->assertNull($a->merge($b));
    }

    #[Test]
    public function mergeWithLeftEmpty(): void
    {
        $a = new Variant(new Types([]));
        $b = new Variant(new Types([new Pattern('X')]));

        /** @var Variant $m */
        $m = $a->merge($b);
        $this->assertSame('X', (string) $m->types);
    }

    #[Test]
    public function mergeVariantsArrayWithArray(): void
    {
        // array+array: produces union of unique values (mergeCases lines 106-109)
        $cls = 'EugeneErg\\ICUMessageFormatParser\\DataTransferObjects\\Select';
        $t = new Types([new Pattern('x')]);
        $v1 = new Variant(types: $t, cases: [$cls => ['g' => ['male', 'female']]]);
        $v2 = new Variant(types: $t, cases: [$cls => ['g' => ['other', 'male']]]);
        $result = $v1->merge($v2);
        $this->assertInstanceOf(Variant::class, $result);
        // merged: male+female+other (unique)
        $this->assertIsArray($result->cases[$cls]['g']);
    }

    #[Test]
    public function mergeVariantsStringConflictsWithArray(): void
    {
        // caseA=string, value IS in caseBArray -> null (lines 85-90)
        $cls = 'EugeneErg\\ICUMessageFormatParser\\DataTransferObjects\\Select';
        $t = new Types([new Pattern('x')]);
        $v1 = new Variant(types: $t, cases: [$cls => ['g' => 'male']]);
        $v2 = new Variant(types: $t, cases: [$cls => ['g' => ['male', 'female']]]);
        $this->assertNull($v1->merge($v2));
    }

    #[Test]
    public function mergeVariantsStringNoConflictWithArray(): void
    {
        // caseA=string, value NOT in caseBArray -> string wins (line 93)
        $cls = 'EugeneErg\\ICUMessageFormatParser\\DataTransferObjects\\Select';
        $t = new Types([new Pattern('x')]);
        $v1 = new Variant(types: $t, cases: [$cls => ['g' => 'other']]);
        $v2 = new Variant(types: $t, cases: [$cls => ['g' => ['male', 'female']]]);
        $result = $v1->merge($v2);
        $this->assertInstanceOf(Variant::class, $result);
        $this->assertSame('other', $result->cases[$cls]['g']);
    }

    #[Test]
    public function mergeVariantsArrayConflictsWithString(): void
    {
        // caseBIsString + caseBString in valueArr -> null (lines 94-102)
        $cls = 'EugeneErg\\ICUMessageFormatParser\\DataTransferObjects\\Select';
        $t = new Types([new Pattern('x')]);
        $v1 = new Variant(types: $t, cases: [$cls => ['g' => ['male', 'female']]]);
        $v2 = new Variant(types: $t, cases: [$cls => ['g' => 'male']]);
        $this->assertNull($v1->merge($v2));
    }
}
