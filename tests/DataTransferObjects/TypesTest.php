<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Contracts\ICUTypeInterface;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Text;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Types;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class TypesTest extends TestCase
{
    #[Test]
    public function emptyTypesToString(): void
    {
        $this->assertSame('', (string) new Types([]));
    }

    #[Test]
    public function patternToString(): void
    {
        $this->assertSame('Hello ', (string) new Types([new Pattern('Hello ')]));
    }

    #[Test]
    public function mixedToString(): void
    {
        $types = new Types([new Pattern('Hello '), new Variable('name')]);
        $this->assertSame('Hello {name}', (string) $types);
    }

    #[Test]
    public function getAllVariantsEmpty(): void
    {
        $variants = (new Types([]))->getAllVariants();
        $this->assertCount(1, $variants);
        $this->assertSame('', (string) $variants[0]->types);
    }

    #[Test]
    public function getAllVariantsWithPattern(): void
    {
        $variants = (new Types([new Pattern('Hello')]))->getAllVariants();
        $this->assertCount(1, $variants);
        $this->assertSame('Hello', (string) $variants[0]->types);
    }

    #[Test]
    public function getAllVariablesEmpty(): void
    {
        $this->assertSame([], (new Types([new Pattern('text')]))->getAllVariables());
    }

    #[Test]
    public function getAllVariablesWithVariable(): void
    {
        $types = new Types([new Variable('name'), new Variable('age'), new Variable('name')]);
        $vars = $types->getAllVariables();
        $this->assertContains('name', $vars);
        $this->assertContains('age', $vars);
        $this->assertCount(2, $vars); // unique
    }

    #[Test]
    public function mapTransformsTypes(): void
    {
        $types = new Types([new Pattern('hello')]);

        $mapped = $types->map(static function (ICUTypeInterface $t) {
            /** @var Pattern $t */
            return new Pattern(strtoupper($t->value));
        });
        $this->assertSame('HELLO', (string) $mapped);
    }

    #[Test]
    public function filterReducesTypes(): void
    {
        $types = new Types([new Pattern('a'), new Variable('x'), new Pattern('b')]);
        $filtered = $types->filter(static fn ($t) => $t instanceof Pattern);
        $this->assertCount(2, $filtered->types);
    }

    #[Test]
    public function quoteWrapsNonPatterns(): void
    {
        $types = new Types([new Variable('name'), new Pattern('hello')]);
        $quoted = $types->quote();
        $this->assertInstanceOf(Text::class, array_values($quoted->types)[0]);
        $this->assertInstanceOf(Pattern::class, array_values($quoted->types)[1]);
    }

    #[Test]
    public function replaceVariableName(): void
    {
        $types = new Types([new Variable('count'), new Pattern(' items')]);
        $replaced = $types->replaceVariableName('count', '#');

        /** @var Variable $firstType */
        $firstType = $replaced->types[0];
        $this->assertSame('#', $firstType->value);
    }

    #[Test]
    public function replaceVariableNameNoMatch(): void
    {
        $types = new Types([new Variable('other')]);
        $replaced = $types->replaceVariableName('count', '#');

        /** @var Variable $firstType */
        $firstType = $replaced->types[0];
        $this->assertSame('other', $firstType->value);
    }

    /**
     * setValues replaces Variable with Text. Text::__toString() wraps value in ICU single
     * quotes, so 'Hello 'Alice'' is the correct ICU serialisation of "Hello " + text("Alice").
     */
    #[Test]
    public function setValuesReplacesVariableWithText(): void
    {
        $types = new Types([new Pattern('Hello '), new Variable('name')]);
        $result = $types->setValues(['name' => 'World']);
        // Text wraps in ICU quotes: Pattern "Hello " + Text "'World'" → "Hello 'World'"
        $this->assertSame("Hello 'World'", (string) $result);
        $this->assertInstanceOf(Text::class, $result->types[1]);
    }

    #[Test]
    public function setValuesNoMatch(): void
    {
        $types = new Types([new Variable('name')]);
        $result = $types->setValues(['other' => 'value']);
        $this->assertInstanceOf(Variable::class, $result->types[0]);
    }

    /**
     * getVariables uses array_filter internally which does NOT re-index keys.
     * Use array_values() to get numerically indexed access.
     */
    #[Test]
    public function getVariables(): void
    {
        $types = new Types([new Pattern('text'), new Variable('x'), new Pattern('more')]);
        $vars = $types->getVariables();
        $this->assertCount(1, $vars->types);
        $indexed = array_values($vars->types);
        $this->assertInstanceOf(Variable::class, $indexed[0]);
        $this->assertSame('x', $indexed[0]->value);
    }

    #[Test]
    public function getVariablesEmpty(): void
    {
        $types = new Types([new Pattern('only patterns')]);
        $this->assertCount(0, $types->getVariables()->types);
    }

    #[Test]
    public function replaceRecursive(): void
    {
        $placeholder = new Pattern('0');
        $replacement = new Types([new Pattern('Hello World')]);
        $result = (new Types([$placeholder]))->replaceRecursive(['0' => $replacement]);
        $this->assertSame('Hello World', (string) $result);
    }

    #[Test]
    public function replaceRecursiveUnknownKeyPassesThrough(): void
    {
        $types = new Types([new Pattern('unchanged')]);
        $result = $types->replaceRecursive(['other' => new Types([new Pattern('X')])]);
        $this->assertSame('unchanged', (string) $result);
    }
}
