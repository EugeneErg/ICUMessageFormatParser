<?php

declare(strict_types=1);

namespace Tests;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Date;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Duration;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Message;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Skeleton;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Ordinal;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Plural;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Select;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\SelectOrdinal;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\SpellOut;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Text;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Time;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variable;
use EugeneErg\ICUMessageFormatParser\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Parser — parsing ICU message strings into Types objects.
 *
 * @internal
 */
final class ParserTest extends TestCase
{
    /**
     * @phpstan-ignore-next-line
     */
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    // -----------------------------------------------------------------------
    // Plain text and variables
    // -----------------------------------------------------------------------

    #[Test]
    public function parsesPlainText(): void
    {
        $types = $this->parser->parse('Hello world!');
        $this->assertSame('Hello world!', (string) $types);
        $this->assertCount(1, $types->types);
        $this->assertInstanceOf(Pattern::class, $types->types[0]);
    }

    #[Test]
    public function parsesVariable(): void
    {
        $types = $this->parser->parse('{name}');
        $this->assertCount(1, $types->types);
        $this->assertInstanceOf(Variable::class, $types->types[0]);
        $this->assertSame('name', $types->types[0]->value);
    }

    #[Test]
    public function parsesVariableInText(): void
    {
        $types = $this->parser->parse('Hello {name}!');
        $this->assertSame('Hello {name}!', (string) $types);
    }

    // -----------------------------------------------------------------------
    // Numeric (positional) placeholders, e.g. {0}, {1}
    // -----------------------------------------------------------------------

    #[Test]
    public function parsesNumericVariable(): void
    {
        $types = $this->parser->parse('{0}');
        $this->assertCount(1, $types->types);
        $this->assertInstanceOf(Variable::class, $types->types[0]);
        $this->assertSame('0', $types->types[0]->value);
        $this->assertSame('{0}', (string) $types);
    }

    #[Test]
    public function parsesMultiDigitNumericVariable(): void
    {
        $types = $this->parser->parse('{123}');
        $this->assertInstanceOf(Variable::class, $types->types[0]);
        $this->assertSame('123', $types->types[0]->value);
        $this->assertSame('{123}', (string) $types);
    }

    #[Test]
    public function parsesMultipleNumericVariablesInText(): void
    {
        $input = 'Hello {0}, you have {1} items';
        $types = $this->parser->parse($input);
        $this->assertSame($input, (string) $types);
        $this->assertInstanceOf(Variable::class, $types->types[1]);
        $this->assertSame('0', $types->types[1]->value);
        $this->assertInstanceOf(Variable::class, $types->types[3]);
        $this->assertSame('1', $types->types[3]->value);
    }

    #[Test]
    public function parsesNumericVariableWithType(): void
    {
        $input = '{0, number}';
        $types = $this->parser->parse($input);
        $this->assertSame($input, (string) $types);
        $this->assertInstanceOf(Number::class, $types->types[0]);
        $this->assertSame('0', $types->types[0]->value);
    }

    #[Test]
    public function parsesNumericVariableWithTypeAndStyle(): void
    {
        $input = '{0, number, integer}';
        $types = $this->parser->parse($input);
        $this->assertSame($input, (string) $types);
        $this->assertInstanceOf(Number::class, $types->types[0]);
        $this->assertSame('0', $types->types[0]->value);
    }

    #[Test]
    public function parsesNumericVariableWithPlural(): void
    {
        $input = '{1, plural, one {# item} other {# items}}';
        $types = $this->parser->parse($input);
        $this->assertSame($input, (string) $types);
        $this->assertInstanceOf(Plural::class, $types->types[0]);
        $this->assertSame('1', $types->types[0]->value);
    }

    // -----------------------------------------------------------------------
    // Quoted text
    // -----------------------------------------------------------------------

    #[Test]
    public function parsesQuotedBraces(): void
    {
        $types = $this->parser->parse("I said '{hello}'");
        $this->assertSame("I said '{hello}'", (string) $types);
    }

    #[Test]
    public function parsesEscapedApostrophe(): void
    {
        $types = $this->parser->parse("It''s a test");
        $this->assertSame("It''s a test", (string) $types);
    }

    // -----------------------------------------------------------------------
    // Select
    // -----------------------------------------------------------------------

    #[Test]
    public function parsesSelect(): void
    {
        $input = '{g, select, male {He} female {She} other {They}}';
        $types = $this->parser->parse($input);
        $this->assertSame($input, (string) $types);
        $this->assertInstanceOf(Select::class, $types->types[0]);
        $this->assertSame('g', $types->types[0]->value);
    }

    // -----------------------------------------------------------------------
    // Plural
    // -----------------------------------------------------------------------

    #[Test]
    public function parsesPlural(): void
    {
        $input = '{n, plural, one {# item} other {# items}}';
        $types = $this->parser->parse($input);
        $this->assertSame($input, (string) $types);
        $this->assertInstanceOf(Plural::class, $types->types[0]);
    }

    #[Test]
    public function parsesPluralWithExactMatch(): void
    {
        $input = '{n, plural, =0 {none} one {# item} other {# items}}';
        $this->assertSame($input, (string) $this->parser->parse($input));
    }

    #[Test]
    public function parsesPluralWithOffset(): void
    {
        $input = '{n, plural, offset:2 one {# other} other {# others}}';
        $types = $this->parser->parse($input);
        $this->assertSame($input, (string) $types);
        $plural = $types->types[0];
        $this->assertInstanceOf(Plural::class, $plural);
        $this->assertSame(2, $plural->offset);
    }

    #[Test]
    public function parsesPluralWithOffsetAndExactMatch(): void
    {
        $input = '{n, plural, offset:2 =0 {nobody} one {# other} other {# others}}';
        $this->assertSame($input, (string) $this->parser->parse($input));
    }

    #[Test]
    public function parsesPluralAllCases(): void
    {
        $input = '{n, plural, zero {0} one {1} two {2} few {3} many {4} other {5}}';
        $this->assertSame($input, (string) $this->parser->parse($input));
    }

    // -----------------------------------------------------------------------
    // SelectOrdinal
    // -----------------------------------------------------------------------

    #[Test]
    public function parsesSelectOrdinal(): void
    {
        $input = '{n, selectordinal, one {#st} two {#nd} few {#rd} other {#th}}';
        $types = $this->parser->parse($input);
        $this->assertSame($input, (string) $types);
        $this->assertInstanceOf(SelectOrdinal::class, $types->types[0]);
    }

    // -----------------------------------------------------------------------
    // Number
    // -----------------------------------------------------------------------

    #[Test]
    public function parsesNumberBare(): void
    {
        $types = $this->parser->parse('{n, number}');
        $this->assertInstanceOf(Number::class, $types->types[0]);
        $this->assertInstanceOf(Skeleton::class, $types->types[0]->options);
        $this->assertSame('{n, number}', (string) $types);
    }

    #[Test]
    public function parsesNumberNamedStyles(): void
    {
        foreach (['integer', 'percent', 'currency'] as $style) {
            $input = "{n, number, {$style}}";
            $this->assertSame($input, (string) $this->parser->parse($input), "Failed for style: {$style}");
        }
    }

    #[Test]
    public function parsesNumberSkeletons(): void
    {
        $skeletons = [
            '::percent',
            '::currency/USD',
            '::currency/EUR',
            '::compact-short',
            '::compact-long',
            '::scientific',
            '::engineering',
            '::.00',
            '::+!',
            '::,?',
            '::rounding-mode-floor',
            '::currency/EUR .00',
            '::scale/100',
            '::measure-unit/length-meter',
            '::notation-simple',
        ];

        foreach ($skeletons as $skeleton) {
            $input = "{n, number, {$skeleton}}";
            $this->assertSame($input, (string) $this->parser->parse($input), "Failed for skeleton: {$skeleton}");
        }
    }

    #[Test]
    public function parsesNumberCustomPattern(): void
    {
        $input = '{n, number, #,##0.00}';
        $types = $this->parser->parse($input);
        // Number with unknown pattern stores as Message — verified via roundtrip
        $this->assertSame($input, (string) $types);
    }

    // -----------------------------------------------------------------------
    // Date
    // -----------------------------------------------------------------------

    #[Test]
    public function parsesDateBare(): void
    {
        $this->assertSame('{d, date}', (string) $this->parser->parse('{d, date}'));
    }

    #[Test]
    public function parsesDateNamedStyles(): void
    {
        foreach (['short', 'long', 'full'] as $style) {
            $input = "{d, date, {$style}}";
            $this->assertSame($input, (string) $this->parser->parse($input));
        }
    }

    #[Test]
    public function parsesDateSkeleton(): void
    {
        $input = '{d, date, ::yMMMd}';
        $types = $this->parser->parse($input);
        $this->assertSame($input, (string) $types);
        $this->assertInstanceOf(Date::class, $types->types[0]);
    }

    #[Test]
    public function parsesDateMediumNormalizesToBare(): void
    {
        // 'medium' is the default for date — normalises to bare form
        $types = $this->parser->parse('{d, date, medium}');
        $this->assertSame('{d, date}', (string) $types);
    }

    // -----------------------------------------------------------------------
    // Time
    // -----------------------------------------------------------------------

    #[Test]
    public function parsesTimeBare(): void
    {
        $this->assertSame('{t, time}', (string) $this->parser->parse('{t, time}'));
    }

    #[Test]
    public function parsesTimeNamedStyles(): void
    {
        foreach (['short', 'long', 'full'] as $style) {
            $input = "{t, time, {$style}}";
            $this->assertSame($input, (string) $this->parser->parse($input));
        }
    }

    #[Test]
    public function parsesTimeSkeleton(): void
    {
        $input = '{t, time, ::Hm}';
        $this->assertSame($input, (string) $this->parser->parse($input));
    }

    #[Test]
    public function parsesTimeMediumNormalizesToBare(): void
    {
        $types = $this->parser->parse('{t, time, medium}');
        $this->assertSame('{t, time}', (string) $types);
    }

    #[Test]
    public function parsesTimeCustomPattern(): void
    {
        $input = '{t, time, HH:mm:ss}';
        $types = $this->parser->parse($input);
        // Time with unknown pattern stores as Message — verified via roundtrip
        $this->assertSame($input, (string) $types);
    }

    // -----------------------------------------------------------------------
    // Other types
    // -----------------------------------------------------------------------

    #[Test]
    public function parsesSpellOut(): void
    {
        $types = $this->parser->parse('{n, spellout}');
        $this->assertInstanceOf(SpellOut::class, $types->types[0]);
        $this->assertSame('{n, spellout}', (string) $types);
    }

    #[Test]
    public function parsesOrdinal(): void
    {
        $types = $this->parser->parse('{n, ordinal}');
        $this->assertInstanceOf(Ordinal::class, $types->types[0]);
    }

    #[Test]
    public function parsesDuration(): void
    {
        $types = $this->parser->parse('{n, duration}');
        $this->assertInstanceOf(Duration::class, $types->types[0]);
    }

    // -----------------------------------------------------------------------
    // Nested
    // -----------------------------------------------------------------------

    #[Test]
    public function parsesNestedSelectInPlural(): void
    {
        $input = '{g, select, male {{n, plural, one {He has # item} other {He has # items}}} other {other}}';
        $this->assertSame($input, (string) $this->parser->parse($input));
    }

    #[Test]
    public function parsesNestedPluralInSelect(): void
    {
        $input = '{n, plural, one {{g, select, male {he} other {they}} has # item} other {{g, select, male {he} other {they}} have # items}}';
        $this->assertSame($input, (string) $this->parser->parse($input));
    }

    // -----------------------------------------------------------------------
    // quote()
    // -----------------------------------------------------------------------

    #[Test]
    public function quoteEscapesApostrophe(): void
    {
        $this->assertSame("It''s", $this->parser->quote("It's"));
    }

    #[Test]
    public function quoteEscapesBraces(): void
    {
        // quote() wraps each { and } individually with apostrophes
        $this->assertSame("'{'hello'}'", $this->parser->quote('{hello}'));
    }

    #[Test]
    public function quoteEscapesBothBraces(): void
    {
        $this->assertSame("'{{'nested'}}'", $this->parser->quote('{{nested}}'));
    }

    #[Test]
    public function quoteEscapesMixedContent(): void
    {
        $this->assertSame("Hello '{'name'}'!", $this->parser->quote('Hello {name}!'));
    }

    // -----------------------------------------------------------------------
    // typesToCases / casesToTypes roundtrip
    // -----------------------------------------------------------------------

    #[Test]
    public function typesToCasesAndBack(): void
    {
        $input = '{g, select, male {He} female {She} other {They}}';
        $types = $this->parser->parse($input);
        $cases = $this->parser->typesToCases($types);
        $restored = $this->parser->casesToTypes($cases);
        $this->assertSame($input, (string) $restored);
    }

    #[Test]
    public function typesToCasesWithMakeKey(): void
    {
        $input = '{n, plural, one {# item} other {# items}}';
        $types = $this->parser->parse($input);
        $cases = $this->parser->typesToCases($types, static fn ($variant, $key) => 'key_' . $key);
        // variator is a Types object whose serialization contains the mapped keys
        $variatorStr = (string) $cases->variator;
        $this->assertStringContainsString('key_0', $variatorStr);
        $this->assertStringContainsString('key_1', $variatorStr);
    }

    #[DataProvider('provideRoundtripCases')]
    #[Test]
    public function roundtrip(string $input): void
    {
        $this->assertSame($input, (string) $this->parser->parse($input));
    }

    // -----------------------------------------------------------------------
    // Roundtrip data provider
    // -----------------------------------------------------------------------

    /**
     * @return array<string, string[]>
     */
    public static function provideRoundtripCases(): iterable
    {
        return [
            'plain text' => ['Hello world!'],
            'variable' => ['{name}'],
            'text+variable' => ['Hello {name}!'],
            'quoted braces' => ["I said '{hello}'"],
            'escaped apostrophe' => ["It''s a test"],
            'select' => ['{g, select, male {He} female {She} other {They}}'],
            'plural basic' => ['{n, plural, one {# item} other {# items}}'],
            'plural =0' => ['{n, plural, =0 {none} one {# item} other {# items}}'],
            'plural offset' => ['{n, plural, offset:2 one {# other} other {# others}}'],
            'selectordinal' => ['{n, selectordinal, one {#st} two {#nd} few {#rd} other {#th}}'],
            'number bare' => ['{n, number}'],
            'number integer' => ['{n, number, integer}'],
            'number percent named' => ['{n, number, percent}'],
            'number currency named' => ['{n, number, currency}'],
            'number ::percent' => ['{n, number, ::percent}'],
            'number ::currency/USD' => ['{n, number, ::currency/USD}'],
            'number ::currency/EUR' => ['{n, number, ::currency/EUR}'],
            'number ::compact-short' => ['{n, number, ::compact-short}'],
            'number ::scientific' => ['{n, number, ::scientific}'],
            'date bare' => ['{d, date}'],
            'date short' => ['{d, date, short}'],
            'date long' => ['{d, date, long}'],
            'date full' => ['{d, date, full}'],
            'date skeleton' => ['{d, date, ::yMMMd}'],
            'time bare' => ['{t, time}'],
            'time short' => ['{t, time, short}'],
            'time skeleton' => ['{t, time, ::Hm}'],
            'spellout' => ['{n, spellout}'],
            'ordinal' => ['{n, ordinal}'],
            'duration' => ['{n, duration}'],
            'nested' => ['{g, select, male {{n, plural, one {He has # item} other {He has # items}}} other {other}}'],
        ];
    }

    // -----------------------------------------------------------------------
    // Parser edge cases (covering uncovered branches)
    // -----------------------------------------------------------------------

    #[Test]
    public function parseOffsetAlone(): void
    {
        // offset:N as standalone token (no following key on same token)
        $input = '{n, plural, offset:2 one {you and # other} other {you and # others}}';
        $types = $this->parser->parse($input);
        $this->assertSame($input, (string) $types);

        /** @var Plural $plural */
        $plural = $types->types[0];
        $this->assertSame(2, $plural->offset);
    }

    #[Test]
    public function parsesTextInTemplateOptions(): void
    {
        // Date/Time skeleton triggers getTemplateOptions with TEXT children
        $input = '{d, date, ::yMMMd}';
        $types = $this->parser->parse($input);
        $this->assertSame($input, (string) $types);
    }

    #[Test]
    public function parsesDateCustomPattern(): void
    {
        // Triggers the Message(...$messageArgs) path in Date::makeOptions
        $input = '{d, date, dd/MM/yyyy}';
        $this->assertSame($input, (string) $this->parser->parse($input));
    }

    #[Test]
    public function parsesTimeCustomPatternRoundtrip(): void
    {
        $input = '{t, time, HH:mm:ss}';
        $this->assertSame($input, (string) $this->parser->parse($input));
    }

    #[Test]
    public function parsesDateOptionsWithQuotedText(): void
    {
        // Covers getTemplateOptions TEXT branch with preceding $message (Parser lines 296-303)
        // quoted text in options creates Result(text) child inside Result(pattern)
        $input = "{d, date, prefix 'quoted'}";
        $types = $this->parser->parse($input);
        // Should parse without error and produce some output
        $this->assertNotEmpty((string) $types);
    }

    // -----------------------------------------------------------------------
    // Parser defensive code coverage via subclassing
    // -----------------------------------------------------------------------

    #[Test]
    public function offsetStandaloneToken(): void
    {
        // offset:2 as standalone (no next key glued) - Parser lines 263-266
        // This happens when offset:N is the very last option token
        // In practice StringParser always glues it, so we test via
        // the normal flow: offset:2 + space + key (Parser separates them correctly)
        $input = '{n, plural, offset:2 one {# other} other {# others}}';
        $types = $this->parser->parse($input);

        /** @var Plural $plural */
        $plural = $types->types[0];
        $this->assertSame(2, $plural->offset);
        $this->assertSame($input, (string) $types);
    }
}
