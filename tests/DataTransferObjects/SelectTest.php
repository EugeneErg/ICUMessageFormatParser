<?php
declare(strict_types = 1);
namespace Tests\DataTransferObjects;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Select;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Text;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Types;
use PHPUnit\Framework\TestCase;

final class SelectTest extends TestCase
{
    private function makeSelect(): Select
    {
        return Select::create('gender', [
            'male'   => [new Pattern('He')],
            'female' => [new Pattern('She')],
            'other'  => [new Pattern('They')],
        ]);
    }

    public function testGetName(): void { self::assertSame('select', Select::getName()); }

    public function testToString(): void
    {
        $s = $this->makeSelect();
        $str = (string) $s;
        self::assertStringContainsString('{gender, select,', $str);
        self::assertStringContainsString('male {He}', $str);
        self::assertStringContainsString('female {She}', $str);
        self::assertStringContainsString('other {They}', $str);
    }

    public function testGetValue(): void { self::assertSame('gender', $this->makeSelect()->getValue()); }

    public function testGetAllVariables(): void
    {
        $vars = $this->makeSelect()->getAllVariables();
        // No sub-variables in this simple case (only Pattern nodes)
        self::assertIsArray($vars);
    }

    public function testGetAllVariantsProducesCorrectCount(): void
    {
        $s = $this->makeSelect();
        $variants = $s->getAllVariants();
        // male, female, other(null) = 3 variants
        self::assertCount(3, $variants);
    }

    public function testGetAllVariantsCasesContainSelectKey(): void
    {
        $variants = $this->makeSelect()->getAllVariants();
        $maleVariant = array_filter($variants, fn ($v) => ($v->cases['select']['gender'] ?? null) === 'male');
        self::assertCount(1, $maleVariant);
    }

    public function testReplaceRecursive(): void
    {
        $s = $this->makeSelect();
        $replaced = $s->replaceRecursive([]);
        self::assertInstanceOf(Select::class, $replaced);
    }

    public function testOnlyOtherReturnsOtherVariants(): void
    {
        $s = Select::create('x', ['other' => [new Pattern('fallback')]]);
        $variants = $s->getAllVariants();
        // only 'other' → no branching, returns other's variants directly
        self::assertCount(1, $variants);
        self::assertSame('fallback', (string) $variants[0]->types);
    }
}
