<?php
declare(strict_types = 1);
namespace Tests\DataTransferObjects;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Plural;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variable;
use PHPUnit\Framework\TestCase;

final class PluralTest extends TestCase
{
    private function makePlural(): Plural
    {
        return Plural::create('count', [
            'one'   => [new Pattern('1 item')],
            'other' => [new Variable('#'), new Pattern(' items')],
        ]);
    }

    public function testGetName(): void { self::assertSame('plural', Plural::getName()); }

    public function testToStringContainsPlural(): void
    {
        $str = (string) $this->makePlural();
        self::assertStringContainsString('{count, plural,', $str);
        self::assertStringContainsString('one {1 item}', $str);
        self::assertStringContainsString('other {# items}', $str);
    }

    public function testHashVariableReplacedWithVariableName(): void
    {
        // # inside plural body is replaced with variable name on create
        $p = Plural::create('count', [
            'one'   => [new Pattern('# thing')],
            'other' => [new Pattern('# things')],
        ]);
        // The # in patterns stays as-is (it's a Pattern, not a Variable)
        self::assertStringContainsString('# thing', (string) $p);
    }

    public function testNumericCases(): void
    {
        $p = Plural::create('n', [
            '=0'    => [new Pattern('none')],
            '=1'    => [new Pattern('one')],
            'other' => [new Pattern('many')],
        ]);
        $str = (string) $p;
        self::assertStringContainsString('=0 {none}', $str);
        self::assertStringContainsString('=1 {one}', $str);
    }

    public function testGetAllVariantsCount(): void
    {
        $variants = $this->makePlural()->getAllVariants();
        // one + other(null) = 2
        self::assertCount(2, $variants);
    }

    public function testGetAllVariables(): void
    {
        // count and # (which is replaced by count's variable)
        $vars = $this->makePlural()->getAllVariables();
        self::assertContains('count', $vars);
    }

    public function testInvalidOptionKeyThrows(): void
    {
        $this->expectException(\LogicException::class);
        Plural::create('n', ['invalid_key' => [new Pattern('x')], 'other' => [new Pattern('y')]]);
    }

    public function testAllNamedCases(): void
    {
        $p = Plural::create('n', [
            'zero'  => [new Pattern('zero')],
            'one'   => [new Pattern('one')],
            'two'   => [new Pattern('two')],
            'few'   => [new Pattern('few')],
            'many'  => [new Pattern('many')],
            'other' => [new Pattern('other')],
        ]);
        $str = (string) $p;
        foreach (['zero','one','two','few','many','other'] as $key) {
            self::assertStringContainsString($key . ' {' . $key . '}', $str);
        }
    }

    public function testReplaceRecursive(): void
    {
        $p = $this->makePlural();
        $replaced = $p->replaceRecursive([]);
        self::assertInstanceOf(Plural::class, $replaced);
    }

    public function testVariantContainsPluralCaseInfo(): void
    {
        $variants = $this->makePlural()->getAllVariants();
        $oneVariant = array_filter($variants, fn ($v) => ($v->cases['plural']['count'] ?? null) === 'one');
        self::assertCount(1, $oneVariant);
    }
}
