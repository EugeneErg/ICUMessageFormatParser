<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Select;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SelectTest extends TestCase
{
    #[Test]
    public function getName(): void
    {
        $this->assertSame('select', Select::getName());
    }

    #[Test]
    public function toString(): void
    {
        $s = $this->makeSelect();
        $str = (string) $s;
        $this->assertStringContainsString('{gender, select,', $str);
        $this->assertStringContainsString('male {He}', $str);
        $this->assertStringContainsString('female {She}', $str);
        $this->assertStringContainsString('other {They}', $str);
    }

    #[Test]
    public function getValue(): void
    {
        $this->assertSame('gender', $this->makeSelect()->getValue());
    }

    #[Test]
    public function getAllVariables(): void
    {
        $vars = $this->makeSelect()->getAllVariables();
        // No sub-variables in this simple case (only Pattern nodes)
        $this->assertIsArray($vars);
    }

    #[Test]
    public function getAllVariantsProducesCorrectCount(): void
    {
        $s = $this->makeSelect();
        $variants = $s->getAllVariants();
        // male, female, other(null) = 3 variants
        $this->assertCount(3, $variants);
    }

    #[Test]
    public function getAllVariantsCasesContainSelectKey(): void
    {
        $variants = $this->makeSelect()->getAllVariants();
        $maleVariant = array_filter($variants, static fn ($v) => ($v->cases['select']['gender'] ?? null) === 'male');
        $this->assertCount(1, $maleVariant);
    }

    #[Test]
    public function replaceRecursive(): void
    {
        $s = $this->makeSelect();
        $replaced = $s->replaceRecursive([]);
        $this->assertInstanceOf(Select::class, $replaced);
    }

    #[Test]
    public function onlyOtherReturnsOtherVariants(): void
    {
        $s = Select::create('x', ['other' => [new Pattern('fallback')]]);
        $variants = $s->getAllVariants();
        // only 'other' → no branching, returns other's variants directly
        $this->assertCount(1, $variants);
        $this->assertSame('fallback', (string) $variants[0]->types);
    }

    private function makeSelect(): Select
    {
        return Select::create('gender', [
            'male' => [new Pattern('He')],
            'female' => [new Pattern('She')],
            'other' => [new Pattern('They')],
        ]);
    }
}
