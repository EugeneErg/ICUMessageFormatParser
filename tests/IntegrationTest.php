<?php

declare(strict_types=1);

namespace Tests;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Cases;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Date;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\DateTimeFormat;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Duration;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Currency;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\PrecisionFraction;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Skeleton;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Ordinal;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Plural;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Select;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\SelectOrdinal;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\SpellOut;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Text;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Time;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Types;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests building Types objects manually, testing getAllVariants,
 * serialisation, and the replaceRecursive/setValues mechanics.
 *
 * @internal
 */
final class IntegrationTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Simple serialisation
    // -----------------------------------------------------------------------

    #[Test]
    public function simplePatternRoundtrip(): void
    {
        $this->assertSame('Hello World', (string) new Types([new Pattern('Hello World')]));
    }

    #[Test]
    public function variableInterpolation(): void
    {
        $types = new Types([new Pattern('Hello '), new Variable('name'), new Pattern('!')]);
        $this->assertSame('Hello {name}!', (string) $types);
    }

    /**
     * setValues replaces Variable nodes with Text nodes.
     * Text::__toString() wraps the value in ICU single-quotes (e.g. 'Alice').
     * That is the correct ICU string representation for a literal text segment.
     */
    #[Test]
    public function setValuesProducesIcuQuotedText(): void
    {
        $types = new Types([new Pattern('Hi '), new Variable('name')]);
        $result = $types->setValues(['name' => 'Alice']);
        // Pattern "Hi " serialises directly; Text "Alice" becomes 'Alice'
        $this->assertSame("Hi 'Alice'", (string) $result);
    }

    #[Test]
    public function setValuesUnknownKeyLeavesVariableIntact(): void
    {
        $types = new Types([new Variable('name')]);
        $result = $types->setValues(['other' => 'x']);
        $this->assertInstanceOf(Variable::class, $result->types[0]);
    }

    // -----------------------------------------------------------------------
    // Select
    // -----------------------------------------------------------------------

    #[Test]
    public function selectFlatteningProducesAllVariants(): void
    {
        $types = new Types([
            Select::create('gender', [
                'male' => [new Pattern('He')],
                'female' => [new Pattern('She')],
                'other' => [new Pattern('They')],
            ]),
            new Pattern(' liked this.'),
        ]);

        $variants = $types->getAllVariants();
        $this->assertCount(3, $variants);

        $texts = array_map(static fn ($v) => (string) $v->types, $variants);
        $this->assertContains('He liked this.', $texts);
        $this->assertContains('She liked this.', $texts);
        $this->assertContains('They liked this.', $texts);
    }

    #[Test]
    public function selectSerialisationContainsAllBranches(): void
    {
        $select = Select::create('gender', [
            'male' => [new Pattern('He')],
            'female' => [new Pattern('She')],
            'other' => [new Pattern('They')],
        ]);
        $str = (string) $select;
        $this->assertStringContainsString('{gender, select,', $str);
        $this->assertStringContainsString('male {He}', $str);
        $this->assertStringContainsString('female {She}', $str);
        $this->assertStringContainsString('other {They}', $str);
    }

    #[Test]
    public function selectVariantCasesCarryBranchLabel(): void
    {
        $variants = Select::create('gender', [
            'male' => [new Pattern('He')],
            'other' => [new Pattern('They')],
        ])->getAllVariants();

        $maleVariants = array_filter($variants, static fn ($v) => ($v->cases['select']['gender'] ?? null) === 'male');
        $this->assertCount(1, $maleVariants);
    }

    // -----------------------------------------------------------------------
    // Plural
    // -----------------------------------------------------------------------

    #[Test]
    public function pluralFlatteningOneAndOther(): void
    {
        $types = new Types([
            Plural::create('count', [
                'one' => [new Pattern('1 item')],
                'other' => [new Variable('#'), new Pattern(' items')],
            ]),
        ]);

        $variants = $types->getAllVariants();
        $this->assertCount(2, $variants);

        $texts = array_map(static fn ($v) => (string) $v->types, $variants);
        $this->assertContains('1 item', $texts);
        // '#' replaced by 'count' variable during create()
        $this->assertContains('{count} items', $texts);
    }

    #[Test]
    public function pluralAllNamedCasesInOutput(): void
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
    public function pluralNumericEquality(): void
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
    public function pluralHashBecomesVariableInVariants(): void
    {
        // Variable('#') inside plural options is replaced by Variable('count')
        $p = Plural::create('count', [
            'one' => [new Pattern('1 item')],
            'other' => [new Variable('#'), new Pattern(' items')],
        ]);
        $variants = (new Types([$p]))->getAllVariants();
        $otherText = (string) $variants[array_key_last($variants)]->types;
        $this->assertStringContainsString('{count}', $otherText);
    }

    // -----------------------------------------------------------------------
    // Nested branching  (Select inside Plural)
    // -----------------------------------------------------------------------

    #[Test]
    public function nestedSelectInsidePluralVariantCount(): void
    {
        // Plural(one|other) × Select(male|other) = 4 variants
        $types = new Types([
            Plural::create('count', [
                'one' => [
                    Select::create('gender', [
                        'male' => [new Pattern('He has 1 item')],
                        'other' => [new Pattern('They have 1 item')],
                    ]),
                ],
                'other' => [
                    Select::create('gender', [
                        'male' => [new Variable('#'), new Pattern(' items for him')],
                        'other' => [new Variable('#'), new Pattern(' items for them')],
                    ]),
                ],
            ]),
        ]);

        $variants = $types->getAllVariants();
        $this->assertCount(4, $variants);
    }

    /**
     * Inside Plural 'other', Variable('#') is replaced by Variable('count') by Plural::create.
     * Inside a nested Select, that Variable becomes {gender} because Select::create replaces
     * '#' in its own options with 'gender'. But the outer Plural's replacement runs first on
     * the array passed to it — the nested Select is created before Plural::create runs, so the
     * Select's Variable('#') → Variable('gender') replacement happens inside Select::create,
     * and Plural::create then replaces the '#' → 'count' at its own level only.
     *
     * Concrete expectation verified empirically:
     *   Variable('#') inside Select inside Plural → Variable('gender') (Select replaces first)
     *   then Plural replaces '#' at its own option level — but the Select was already created.
     */
    #[Test]
    public function nestedSelectInsidePluralContentTexts(): void
    {
        $types = new Types([
            Plural::create('count', [
                'one' => [
                    Select::create('gender', [
                        'male' => [new Pattern('He has 1 item')],
                        'other' => [new Pattern('They have 1 item')],
                    ]),
                ],
                'other' => [
                    Select::create('gender', [
                        'male' => [new Variable('#'), new Pattern(' items for him')],
                        'other' => [new Variable('#'), new Pattern(' items for them')],
                    ]),
                ],
            ]),
        ]);

        $texts = array_map(static fn ($v) => (string) $v->types, $types->getAllVariants());
        $this->assertContains('He has 1 item', $texts);
        $this->assertContains('They have 1 item', $texts);
        // '#' in the nested Select was not under Plural's replaceVariableName scope
        // (Select::create already ran); the actual variable name in variants is 'gender'
        $hasHim = array_filter($texts, static fn ($t) => str_contains($t, 'items for him'));
        $hasThem = array_filter($texts, static fn ($t) => str_contains($t, 'items for them'));
        $this->assertNotEmpty($hasHim);
        $this->assertNotEmpty($hasThem);
    }

    // -----------------------------------------------------------------------
    // Cases / replaceRecursive
    // -----------------------------------------------------------------------

    #[Test]
    public function casesStructure(): void
    {
        $types = [new Types([new Pattern('Hello')]), new Types([new Pattern('World')])];
        $cases = new Cases($types, new Types([new Pattern('test')]));
        $this->assertCount(2, $cases->types);
        $this->assertSame('test', (string) $cases->variator);
    }

    #[Test]
    public function replaceRecursivePlaceholderReplacement(): void
    {
        $placeholder = new Types([new Pattern('PLACEHOLDER')]);
        $replacement = new Types([new Pattern('REPLACED')]);
        $result = $placeholder->replaceRecursive(['PLACEHOLDER' => $replacement]);
        $this->assertSame('REPLACED', (string) $result);
    }

    #[Test]
    public function replaceRecursiveOnSelectPassesThrough(): void
    {
        $select = Select::create('gender', [
            'male' => [new Pattern('He')],
            'other' => [new Pattern('They')],
        ]);
        $types = new Types([$select]);
        $result = $types->replaceRecursive([]);
        $this->assertSame((string) $types, (string) $result);
    }

    // -----------------------------------------------------------------------
    // getAllVariables
    // -----------------------------------------------------------------------

    #[Test]
    public function getAllVariablesDeduplicates(): void
    {
        $types = new Types([
            new Variable('name'),
            new Pattern(' has '),
            new Variable('count'),
            new Pattern(' items from '),
            new Variable('name'), // duplicate
        ]);
        $vars = $types->getAllVariables();
        sort($vars);
        $this->assertSame(['count', 'name'], $vars);
    }

    /**
     * AbstractSelect::getAllVariables() returns variables found in the CONTENT of branches,
     * not the switch variable itself ('gender'). The select variable name is the ICU argument
     * name, not a placeholder to be substituted.
     */
    #[Test]
    public function getAllVariablesFromSelectReturnsContentVarsOnly(): void
    {
        $s = Select::create('gender', [
            'male' => [new Variable('name'), new Pattern(' is a man')],
            'other' => [new Variable('name'), new Pattern(' is a person')],
        ]);
        $vars = $s->getAllVariables();
        $this->assertContains('name', $vars);
        // 'gender' is the switch argument, not returned as a substitution variable
        $this->assertNotContains('gender', $vars);
    }

    #[Test]
    public function getAllVariablesFromPluralIncludesBothLevels(): void
    {
        $p = Plural::create('count', [
            'one' => [new Pattern('1 item')],
            'other' => [new Variable('#'), new Pattern(' items')],
        ]);
        $vars = $p->getAllVariables();
        // '#' is replaced by 'count' in plural options
        $this->assertContains('count', $vars);
    }

    // -----------------------------------------------------------------------
    // Quote
    // -----------------------------------------------------------------------

    #[Test]
    public function quoteConvertsVariablesToText(): void
    {
        $types = new Types([new Variable('name'), new Pattern(' hello')]);
        $quoted = $types->quote();
        $arr = array_values($quoted->types);
        $this->assertInstanceOf(Text::class, $arr[0]);
        $this->assertInstanceOf(Pattern::class, $arr[1]);
    }

    // -----------------------------------------------------------------------
    // SelectOrdinal
    // -----------------------------------------------------------------------

    #[Test]
    public function selectOrdinalVariants(): void
    {
        $so = SelectOrdinal::create('place', [
            'one' => [new Pattern('1st')],
            'two' => [new Pattern('2nd')],
            'few' => [new Pattern('3rd')],
            'other' => [new Pattern('#th')],
        ]);
        $this->assertCount(4, $so->getAllVariants());
    }

    #[Test]
    public function selectOrdinalToString(): void
    {
        $so = SelectOrdinal::create('n', [
            'one' => [new Pattern('st')],
            'other' => [new Pattern('th')],
        ]);
        $str = (string) $so;
        $this->assertStringContainsString('{n, selectordinal,', $str);
        $this->assertStringContainsString('one {st}', $str);
        $this->assertStringContainsString('other {th}', $str);
    }

    // -----------------------------------------------------------------------
    // Number
    // -----------------------------------------------------------------------

    #[Test]
    public function numberWithCurrencySkeletonInMessage(): void
    {
        $n = new Number('price', new Skeleton(new Currency('EUR')));
        $str = (string) $n;
        $this->assertStringContainsString('price, number', $str);
        $this->assertStringContainsString('currency/EUR', $str);
    }

    #[Test]
    public function numberWithFractionPrecision(): void
    {
        $n = new Number('amount', new Skeleton(precision: new PrecisionFraction(2, 2)));
        $str = (string) $n;
        $this->assertStringContainsString('.00', $str);
    }

    // -----------------------------------------------------------------------
    // Date / Time
    // -----------------------------------------------------------------------

    #[Test]
    public function dateInMessage(): void
    {
        $types = new Types([new Pattern('Order placed on '), new Date('orderDate', DateTimeFormat::Long)]);
        $this->assertSame('Order placed on {orderDate, date, long}', (string) $types);
    }

    #[Test]
    public function timeInMessage(): void
    {
        $types = new Types([new Pattern('At '), new Time('eventTime', DateTimeFormat::Short)]);
        $this->assertSame('At {eventTime, time, short}', (string) $types);
    }

    // -----------------------------------------------------------------------
    // SpellOut / Duration / Ordinal
    // -----------------------------------------------------------------------

    #[Test]
    public function spellOutInMessage(): void
    {
        $types = new Types([new Pattern('You have '), new SpellOut('count'), new Pattern(' apples')]);
        $this->assertSame('You have {count, spellout} apples', (string) $types);
    }

    #[Test]
    public function durationInMessage(): void
    {
        $types = new Types([new Pattern('Elapsed: '), new Duration('elapsed')]);
        $this->assertSame('Elapsed: {elapsed, duration}', (string) $types);
    }

    #[Test]
    public function ordinalInMessage(): void
    {
        $types = new Types([new Pattern('You came in '), new Ordinal('rank'), new Pattern(' place')]);
        $this->assertSame('You came in {rank, ordinal} place', (string) $types);
    }

    // -----------------------------------------------------------------------
    // Complex multi-variable message
    // -----------------------------------------------------------------------

    #[Test]
    public function complexMessageWithAllTypes(): void
    {
        $types = new Types([
            Select::create('gender', [
                'male' => [new Variable('name'), new Pattern(' received')],
                'female' => [new Variable('name'), new Pattern(' received')],
                'other' => [new Variable('name'), new Pattern(' received')],
            ]),
            new Pattern(' '),
            Plural::create('count', [
                'one' => [new Pattern('1 message')],
                'other' => [new Variable('#'), new Pattern(' messages')],
            ]),
            new Pattern(' on '),
            new Date('date', DateTimeFormat::Short),
        ]);

        $variants = $types->getAllVariants();
        // 3 gender × 2 plural = 6 variants
        $this->assertCount(6, $variants);

        foreach ($variants as $v) {
            $this->assertStringContainsString('{date, date, short}', (string) $v->types);
        }
    }

    // -----------------------------------------------------------------------
    // plural / selectordinal offset
    // -----------------------------------------------------------------------

    #[Test]
    public function pluralOffsetSerialisation(): void
    {
        $p = Plural::create('n', [
            'offset' => 2,
            '=2' => [new Pattern('just the two of you')],
            'one' => [new Pattern('you and # other person')],
            'other' => [new Pattern('you and # other people')],
        ]);

        $str = (string) $p;
        $this->assertStringContainsString('{n, plural, offset:2', $str);
        $this->assertStringContainsString('=2 {just the two of you}', $str);
        $this->assertStringContainsString('one {you and # other person}', $str);
        $this->assertStringContainsString('other {you and # other people}', $str);
    }

    #[Test]
    public function pluralOffsetInTypes(): void
    {
        $types = new Types([
            new Pattern('Join: '),
            Plural::create('n', [
                'offset' => 1,
                '=1' => [new Pattern('just you')],
                'one' => [new Pattern('you and # other')],
                'other' => [new Pattern('you and # others')],
            ]),
        ]);

        $this->assertStringContainsString('offset:1', (string) $types);
        // Variants: =1 + one + other(null) = 3
        $this->assertCount(3, $types->getAllVariants());
    }

    #[Test]
    public function pluralOffsetZeroOmittedFromOutput(): void
    {
        $p = Plural::create('n', ['one' => [new Pattern('item')], 'other' => [new Pattern('items')]]);
        $this->assertStringNotContainsString('offset:', (string) $p);
    }

    #[Test]
    public function pluralOffsetPreservedThroughReplaceRecursive(): void
    {
        $p = Plural::create('n', ['offset' => 3, 'other' => [new Pattern('many')]]);
        $result = (new Types([$p]))->replaceRecursive([]);
        $this->assertStringContainsString('offset:3', (string) $result);
    }

    #[Test]
    public function selectOrdinalWithOffset(): void
    {
        $so = SelectOrdinal::create('place', [
            'offset' => 1,
            'one' => [new Pattern('#st runner-up')],
            'other' => [new Pattern('#th runner-up')],
        ]);
        $this->assertStringContainsString('selectordinal, offset:1', (string) $so);
    }

    #[Test]
    public function pluralOffsetCreateApiWithIntKey(): void
    {
        // 'offset' => int is the programmatic API; Parser::getNestedOptions
        // converts "offset:N" string key to ['offset' => N] before calling create()
        $p = Plural::create('n', [
            'offset' => 2,
            'one' => [new Pattern('you and # other')],
            'other' => [new Pattern('you and # others')],
        ]);
        $this->assertSame(2, $p->offset);
        $this->assertStringContainsString('offset:2', (string) $p);
    }
}
