<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Plural;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Types;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variable;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class PluralTest extends TestCase
{
    #[Test]
    public function getName(): void
    {
        $this->assertSame('plural', Plural::getName());
    }

    #[Test]
    public function toStringContainsPlural(): void
    {
        $str = (string) $this->makePlural();
        $this->assertStringContainsString('{count, plural,', $str);
        $this->assertStringContainsString('one {1 item}', $str);
        $this->assertStringContainsString('other {# items}', $str);
    }

    #[Test]
    public function hashVariableReplacedWithVariableName(): void
    {
        // # inside plural body is replaced with variable name on create
        $p = Plural::create('count', [
            'one' => [new Pattern('# thing')],
            'other' => [new Pattern('# things')],
        ]);
        // The # in patterns stays as-is (it's a Pattern, not a Variable)
        $this->assertStringContainsString('# thing', (string) $p);
    }

    #[Test]
    public function numericCases(): void
    {
        $p = Plural::create('n', [
            '=0' => [new Pattern('none')],
            '=1' => [new Pattern('one')],
            'other' => [new Pattern('many')],
        ]);
        $str = (string) $p;
        $this->assertStringContainsString('=0 {none}', $str);
        $this->assertStringContainsString('=1 {one}', $str);
    }

    #[Test]
    public function getAllVariantsCount(): void
    {
        $variants = $this->makePlural()->getAllVariants();
        // one + other(null) = 2
        $this->assertCount(2, $variants);
    }

    #[Test]
    public function getAllVariables(): void
    {
        // count and # (which is replaced by count's variable)
        $vars = $this->makePlural()->getAllVariables();
        $this->assertContains('count', $vars);
    }

    #[Test]
    public function invalidOptionKeyThrows(): void
    {
        $this->expectException(LogicException::class);
        Plural::create('n', ['invalid_key' => [new Pattern('x')], 'other' => [new Pattern('y')]]);
    }

    #[Test]
    public function allNamedCases(): void
    {
        $p = Plural::create('n', [
            'zero' => [new Pattern('zero')],
            'one' => [new Pattern('one')],
            'two' => [new Pattern('two')],
            'few' => [new Pattern('few')],
            'many' => [new Pattern('many')],
            'other' => [new Pattern('other')],
        ]);
        $str = (string) $p;

        foreach (['zero', 'one', 'two', 'few', 'many', 'other'] as $key) {
            $this->assertStringContainsString($key . ' {' . $key . '}', $str);
        }
    }

    #[Test]
    public function replaceRecursive(): void
    {
        $p = $this->makePlural();
        $replaced = $p->replaceRecursive([]);
        $this->assertInstanceOf(Plural::class, $replaced);
    }

    #[Test]
    public function variantContainsPluralCaseInfo(): void
    {
        $variants = $this->makePlural()->getAllVariants();
        $oneVariant = array_filter($variants, static fn ($v) => ($v->cases['plural']['count'] ?? null) === 'one');
        $this->assertCount(1, $oneVariant);
    }

    // -----------------------------------------------------------------------
    // offset support
    // -----------------------------------------------------------------------

    #[Test]
    public function offsetDefaultIsZero(): void
    {
        $p = Plural::create('n', ['one' => [new Pattern('item')], 'other' => [new Pattern('items')]]);
        $this->assertSame(0, $p->offset);
    }

    #[Test]
    public function offsetStoredCorrectly(): void
    {
        $p = Plural::create('n', ['offset' => 2, 'one' => [new Pattern('# other')], 'other' => [new Pattern('# others')]]);
        $this->assertSame(2, $p->offset);
    }

    #[Test]
    public function offsetSerialisation(): void
    {
        $p = Plural::create('n', [
            'offset' => 2,
            '=2' => [new Pattern('just you two')],
            'one' => [new Pattern('you and # other')],
            'other' => [new Pattern('you and # others')],
        ]);
        $str = (string) $p;
        $this->assertStringContainsString('offset:2', $str);
        $this->assertStringContainsString('=2 {just you two}', $str);
        $this->assertStringContainsString('one {you and # other}', $str);
        $this->assertStringContainsString('other {you and # others}', $str);
        $this->assertMatchesRegularExpression('/\\{n, plural, offset:2 /', $str);
    }

    #[Test]
    public function offsetZeroNotSerialisedExplicitly(): void
    {
        $p = Plural::create('n', ['other' => [new Pattern('items')]]);
        $this->assertStringNotContainsString('offset:', (string) $p);
    }

    #[Test]
    public function offsetPreservedInReplaceRecursive(): void
    {
        $p = Plural::create('n', ['offset' => 3, 'other' => [new Pattern('many')]]);
        $replaced = $p->replaceRecursive([]);
        $this->assertSame(3, $replaced->offset);
    }

    #[Test]
    public function offsetWithNumericExactMatch(): void
    {
        $p = Plural::create('n', [
            'offset' => 1,
            '=1' => [new Pattern('only you')],
            'one' => [new Pattern('you and # other')],
            'other' => [new Pattern('you and # others')],
        ]);
        $str = (string) $p;
        $this->assertStringContainsString('=1 {only you}', $str);
        $this->assertStringContainsString('offset:1', $str);
    }

    #[Test]
    public function pluralDirectConstructorWithOffset(): void
    {
        $p = new Plural(
            value: 'count',
            other: new Types([new Pattern('# others')]),
            one: new Types([new Pattern('# other')]),
            offset: 5,
        );
        $this->assertSame(5, $p->offset);
        $this->assertStringContainsString('offset:5', (string) $p);
    }

    private function makePlural(): Plural
    {
        return Plural::create('count', [
            'one' => [new Pattern('1 item')],
            'other' => [new Variable('#'), new Pattern(' items')],
        ]);
    }
}
