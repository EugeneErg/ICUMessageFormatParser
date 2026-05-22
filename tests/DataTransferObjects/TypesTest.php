<?php
declare(strict_types = 1);
namespace Tests\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Text;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variable;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Types;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variant;
use PHPUnit\Framework\TestCase;

final class TypesTest extends TestCase
{
    public function testEmptyTypesToString(): void
    {
        self::assertSame('', (string) new Types([]));
    }

    public function testPatternToString(): void
    {
        self::assertSame('Hello ', (string) new Types([new Pattern('Hello ')]));
    }

    public function testMixedToString(): void
    {
        $types = new Types([new Pattern('Hello '), new Variable('name')]);
        self::assertSame('Hello {name}', (string) $types);
    }

    public function testGetAllVariantsEmpty(): void
    {
        $variants = (new Types([]))->getAllVariants();
        self::assertCount(1, $variants);
        self::assertSame('', (string) $variants[0]->types);
    }

    public function testGetAllVariantsWithPattern(): void
    {
        $variants = (new Types([new Pattern('Hello')]))->getAllVariants();
        self::assertCount(1, $variants);
        self::assertSame('Hello', (string) $variants[0]->types);
    }

    public function testGetAllVariablesEmpty(): void
    {
        self::assertSame([], (new Types([new Pattern('text')]))->getAllVariables());
    }

    public function testGetAllVariablesWithVariable(): void
    {
        $types = new Types([new Variable('name'), new Variable('age'), new Variable('name')]);
        $vars = $types->getAllVariables();
        self::assertContains('name', $vars);
        self::assertContains('age', $vars);
        self::assertCount(2, $vars); // unique
    }

    public function testMapTransformsTypes(): void
    {
        $types = new Types([new Pattern('hello')]);
        $mapped = $types->map(fn ($t) => new Pattern(strtoupper($t->value)));
        self::assertSame('HELLO', (string) $mapped);
    }

    public function testFilterReducesTypes(): void
    {
        $types = new Types([new Pattern('a'), new Variable('x'), new Pattern('b')]);
        $filtered = $types->filter(fn ($t) => $t instanceof Pattern);
        self::assertCount(2, $filtered->types);
    }

    public function testQuoteWrapsNonPatterns(): void
    {
        $types = new Types([new Variable('name'), new Pattern('hello')]);
        $quoted = $types->quote();
        self::assertInstanceOf(Text::class,    array_values($quoted->types)[0]);
        self::assertInstanceOf(Pattern::class, array_values($quoted->types)[1]);
    }

    public function testReplaceVariableName(): void
    {
        $types = new Types([new Variable('count'), new Pattern(' items')]);
        $replaced = $types->replaceVariableName('count', '#');
        self::assertSame('#', $replaced->types[0]->value);
    }

    public function testReplaceVariableNameNoMatch(): void
    {
        $types = new Types([new Variable('other')]);
        $replaced = $types->replaceVariableName('count', '#');
        self::assertSame('other', $replaced->types[0]->value);
    }

    /**
     * setValues replaces Variable with Text. Text::__toString() wraps value in ICU single
     * quotes, so 'Hello 'Alice'' is the correct ICU serialisation of "Hello " + text("Alice").
     */
    public function testSetValuesReplacesVariableWithText(): void
    {
        $types = new Types([new Pattern('Hello '), new Variable('name')]);
        $result = $types->setValues(['name' => 'World']);
        // Text wraps in ICU quotes: Pattern "Hello " + Text "'World'" → "Hello 'World'"
        self::assertSame("Hello 'World'", (string) $result);
        self::assertInstanceOf(Text::class, $result->types[1]);
    }

    public function testSetValuesNoMatch(): void
    {
        $types = new Types([new Variable('name')]);
        $result = $types->setValues(['other' => 'value']);
        self::assertInstanceOf(Variable::class, $result->types[0]);
    }

    /**
     * getVariables uses array_filter internally which does NOT re-index keys.
     * Use array_values() to get numerically indexed access.
     */
    public function testGetVariables(): void
    {
        $types = new Types([new Pattern('text'), new Variable('x'), new Pattern('more')]);
        $vars = $types->getVariables();
        self::assertCount(1, $vars->types);
        $indexed = array_values($vars->types);
        self::assertInstanceOf(Variable::class, $indexed[0]);
        self::assertSame('x', $indexed[0]->value);
    }

    public function testGetVariablesEmpty(): void
    {
        $types = new Types([new Pattern('only patterns')]);
        self::assertCount(0, $types->getVariables()->types);
    }

    public function testReplaceRecursive(): void
    {
        $placeholder = new Pattern('0');
        $replacement = new Types([new Pattern('Hello World')]);
        $result = (new Types([$placeholder]))->replaceRecursive(['0' => $replacement]);
        self::assertSame('Hello World', (string) $result);
    }

    public function testReplaceRecursiveUnknownKeyPassesThrough(): void
    {
        $types = new Types([new Pattern('unchanged')]);
        $result = $types->replaceRecursive(['other' => new Types([new Pattern('X')])]);
        self::assertSame('unchanged', (string) $result);
    }
}
