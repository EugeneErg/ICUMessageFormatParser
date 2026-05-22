<?php

declare(strict_types = 1);

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
use PHPUnit\Framework\TestCase;

/**
 * Integration tests building Types objects manually, testing getAllVariants,
 * serialisation, and the replaceRecursive/setValues mechanics.
 */
final class IntegrationTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Simple serialisation
    // -----------------------------------------------------------------------

    public function testSimplePatternRoundtrip(): void
    {
        self::assertSame('Hello World', (string) new Types([new Pattern('Hello World')]));
    }

    public function testVariableInterpolation(): void
    {
        $types = new Types([new Pattern('Hello '), new Variable('name'), new Pattern('!')]);
        self::assertSame('Hello {name}!', (string) $types);
    }

    /**
     * setValues replaces Variable nodes with Text nodes.
     * Text::__toString() wraps the value in ICU single-quotes (e.g. 'Alice').
     * That is the correct ICU string representation for a literal text segment.
     */
    public function testSetValuesProducesIcuQuotedText(): void
    {
        $types = new Types([new Pattern('Hi '), new Variable('name')]);
        $result = $types->setValues(['name' => 'Alice']);
        // Pattern "Hi " serialises directly; Text "Alice" becomes 'Alice'
        self::assertSame("Hi 'Alice'", (string) $result);
    }

    public function testSetValuesUnknownKeyLeavesVariableIntact(): void
    {
        $types = new Types([new Variable('name')]);
        $result = $types->setValues(['other' => 'x']);
        self::assertInstanceOf(Variable::class, $result->types[0]);
    }

    // -----------------------------------------------------------------------
    // Select
    // -----------------------------------------------------------------------

    public function testSelectFlatteningProducesAllVariants(): void
    {
        $types = new Types([
            Select::create('gender', [
                'male'   => [new Pattern('He')],
                'female' => [new Pattern('She')],
                'other'  => [new Pattern('They')],
            ]),
            new Pattern(' liked this.'),
        ]);

        $variants = $types->getAllVariants();
        self::assertCount(3, $variants);

        $texts = array_map(fn ($v) => (string) $v->types, $variants);
        self::assertContains('He liked this.',   $texts);
        self::assertContains('She liked this.',  $texts);
        self::assertContains('They liked this.', $texts);
    }

    public function testSelectSerialisationContainsAllBranches(): void
    {
        $select = Select::create('gender', [
            'male'   => [new Pattern('He')],
            'female' => [new Pattern('She')],
            'other'  => [new Pattern('They')],
        ]);
        $str = (string) $select;
        self::assertStringContainsString('{gender, select,', $str);
        self::assertStringContainsString('male {He}',        $str);
        self::assertStringContainsString('female {She}',     $str);
        self::assertStringContainsString('other {They}',     $str);
    }

    public function testSelectVariantCasesCarryBranchLabel(): void
    {
        $variants = Select::create('gender', [
            'male'  => [new Pattern('He')],
            'other' => [new Pattern('They')],
        ])->getAllVariants();

        $maleVariants = array_filter($variants, fn ($v) => ($v->cases['select']['gender'] ?? null) === 'male');
        self::assertCount(1, $maleVariants);
    }

    // -----------------------------------------------------------------------
    // Plural
    // -----------------------------------------------------------------------

    public function testPluralFlatteningOneAndOther(): void
    {
        $types = new Types([
            Plural::create('count', [
                'one'   => [new Pattern('1 item')],
                'other' => [new Variable('#'), new Pattern(' items')],
            ]),
        ]);

        $variants = $types->getAllVariants();
        self::assertCount(2, $variants);

        $texts = array_map(fn ($v) => (string) $v->types, $variants);
        self::assertContains('1 item',        $texts);
        // '#' replaced by 'count' variable during create()
        self::assertContains('{count} items', $texts);
    }

    public function testPluralAllNamedCasesInOutput(): void
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

    public function testPluralNumericEquality(): void
    {
        $p = Plural::create('n', [
            '=0'    => [new Pattern('none')],
            '=1'    => [new Pattern('one')],
            'other' => [new Pattern('many')],
        ]);
        $str = (string) $p;
        self::assertStringContainsString('=0 {none}', $str);
        self::assertStringContainsString('=1 {one}',  $str);
    }

    public function testPluralHashBecomesVariableInVariants(): void
    {
        // Variable('#') inside plural options is replaced by Variable('count')
        $p = Plural::create('count', [
            'one'   => [new Pattern('1 item')],
            'other' => [new Variable('#'), new Pattern(' items')],
        ]);
        $variants = (new Types([$p]))->getAllVariants();
        $otherText = (string) $variants[array_key_last($variants)]->types;
        self::assertStringContainsString('{count}', $otherText);
    }

    // -----------------------------------------------------------------------
    // Nested branching  (Select inside Plural)
    // -----------------------------------------------------------------------

    public function testNestedSelectInsidePluralVariantCount(): void
    {
        // Plural(one|other) × Select(male|other) = 4 variants
        $types = new Types([
            Plural::create('count', [
                'one'   => [
                    Select::create('gender', [
                        'male'  => [new Pattern('He has 1 item')],
                        'other' => [new Pattern('They have 1 item')],
                    ]),
                ],
                'other' => [
                    Select::create('gender', [
                        'male'  => [new Variable('#'), new Pattern(' items for him')],
                        'other' => [new Variable('#'), new Pattern(' items for them')],
                    ]),
                ],
            ]),
        ]);

        $variants = $types->getAllVariants();
        self::assertCount(4, $variants);
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
    public function testNestedSelectInsidePluralContentTexts(): void
    {
        $types = new Types([
            Plural::create('count', [
                'one'   => [
                    Select::create('gender', [
                        'male'  => [new Pattern('He has 1 item')],
                        'other' => [new Pattern('They have 1 item')],
                    ]),
                ],
                'other' => [
                    Select::create('gender', [
                        'male'  => [new Variable('#'), new Pattern(' items for him')],
                        'other' => [new Variable('#'), new Pattern(' items for them')],
                    ]),
                ],
            ]),
        ]);

        $texts = array_map(fn ($v) => (string) $v->types, $types->getAllVariants());
        self::assertContains('He has 1 item',    $texts);
        self::assertContains('They have 1 item', $texts);
        // '#' in the nested Select was not under Plural's replaceVariableName scope
        // (Select::create already ran); the actual variable name in variants is 'gender'
        $hasHim   = array_filter($texts, fn ($t) => str_contains($t, 'items for him'));
        $hasThem  = array_filter($texts, fn ($t) => str_contains($t, 'items for them'));
        self::assertNotEmpty($hasHim);
        self::assertNotEmpty($hasThem);
    }

    // -----------------------------------------------------------------------
    // Cases / replaceRecursive
    // -----------------------------------------------------------------------

    public function testCasesStructure(): void
    {
        $types   = [new Types([new Pattern('Hello')]), new Types([new Pattern('World')])];
        $cases   = new Cases($types, new Types([new Pattern('test')]));
        self::assertCount(2, $cases->types);
        self::assertSame('test', (string) $cases->variator);
    }

    public function testReplaceRecursivePlaceholderReplacement(): void
    {
        $placeholder  = new Types([new Pattern('PLACEHOLDER')]);
        $replacement  = new Types([new Pattern('REPLACED')]);
        $result = $placeholder->replaceRecursive(['PLACEHOLDER' => $replacement]);
        self::assertSame('REPLACED', (string) $result);
    }

    public function testReplaceRecursiveOnSelectPassesThrough(): void
    {
        $select = Select::create('gender', [
            'male'  => [new Pattern('He')],
            'other' => [new Pattern('They')],
        ]);
        $types  = new Types([$select]);
        $result = $types->replaceRecursive([]);
        self::assertSame((string) $types, (string) $result);
    }

    // -----------------------------------------------------------------------
    // getAllVariables
    // -----------------------------------------------------------------------

    public function testGetAllVariablesDeduplicates(): void
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
        self::assertSame(['count', 'name'], $vars);
    }

    /**
     * AbstractSelect::getAllVariables() returns variables found in the CONTENT of branches,
     * not the switch variable itself ('gender'). The select variable name is the ICU argument
     * name, not a placeholder to be substituted.
     */
    public function testGetAllVariablesFromSelectReturnsContentVarsOnly(): void
    {
        $s = Select::create('gender', [
            'male'  => [new Variable('name'), new Pattern(' is a man')],
            'other' => [new Variable('name'), new Pattern(' is a person')],
        ]);
        $vars = $s->getAllVariables();
        self::assertContains('name', $vars);
        // 'gender' is the switch argument, not returned as a substitution variable
        self::assertNotContains('gender', $vars);
    }

    public function testGetAllVariablesFromPluralIncludesBothLevels(): void
    {
        $p = Plural::create('count', [
            'one'   => [new Pattern('1 item')],
            'other' => [new Variable('#'), new Pattern(' items')],
        ]);
        $vars = $p->getAllVariables();
        // '#' is replaced by 'count' in plural options
        self::assertContains('count', $vars);
    }

    // -----------------------------------------------------------------------
    // Quote
    // -----------------------------------------------------------------------

    public function testQuoteConvertsVariablesToText(): void
    {
        $types  = new Types([new Variable('name'), new Pattern(' hello')]);
        $quoted = $types->quote();
        $arr    = array_values($quoted->types);
        self::assertInstanceOf(Text::class,    $arr[0]);
        self::assertInstanceOf(Pattern::class, $arr[1]);
    }

    // -----------------------------------------------------------------------
    // SelectOrdinal
    // -----------------------------------------------------------------------

    public function testSelectOrdinalVariants(): void
    {
        $so = SelectOrdinal::create('place', [
            'one'   => [new Pattern('1st')],
            'two'   => [new Pattern('2nd')],
            'few'   => [new Pattern('3rd')],
            'other' => [new Pattern('#th')],
        ]);
        self::assertCount(4, $so->getAllVariants());
    }

    public function testSelectOrdinalToString(): void
    {
        $so  = SelectOrdinal::create('n', [
            'one'   => [new Pattern('st')],
            'other' => [new Pattern('th')],
        ]);
        $str = (string) $so;
        self::assertStringContainsString('{n, selectordinal,', $str);
        self::assertStringContainsString('one {st}',  $str);
        self::assertStringContainsString('other {th}', $str);
    }

    // -----------------------------------------------------------------------
    // Number
    // -----------------------------------------------------------------------

    public function testNumberWithCurrencySkeletonInMessage(): void
    {
        $n   = new Number('price', new Skeleton(new Currency('EUR')));
        $str = (string) $n;
        self::assertStringContainsString('price, number', $str);
        self::assertStringContainsString('currency/EUR',  $str);
    }

    public function testNumberWithFractionPrecision(): void
    {
        $n   = new Number('amount', new Skeleton(precision: new PrecisionFraction(2, 2)));
        $str = (string) $n;
        self::assertStringContainsString('.00', $str);
    }

    // -----------------------------------------------------------------------
    // Date / Time
    // -----------------------------------------------------------------------

    public function testDateInMessage(): void
    {
        $types = new Types([new Pattern('Order placed on '), new Date('orderDate', DateTimeFormat::Long)]);
        self::assertSame('Order placed on {orderDate, date, long}', (string) $types);
    }

    public function testTimeInMessage(): void
    {
        $types = new Types([new Pattern('At '), new Time('eventTime', DateTimeFormat::Short)]);
        self::assertSame('At {eventTime, time, short}', (string) $types);
    }

    // -----------------------------------------------------------------------
    // SpellOut / Duration / Ordinal
    // -----------------------------------------------------------------------

    public function testSpellOutInMessage(): void
    {
        $types = new Types([new Pattern('You have '), new SpellOut('count'), new Pattern(' apples')]);
        self::assertSame('You have {count, spellout} apples', (string) $types);
    }

    public function testDurationInMessage(): void
    {
        $types = new Types([new Pattern('Elapsed: '), new Duration('elapsed')]);
        self::assertSame('Elapsed: {elapsed, duration}', (string) $types);
    }

    public function testOrdinalInMessage(): void
    {
        $types = new Types([new Pattern('You came in '), new Ordinal('rank'), new Pattern(' place')]);
        self::assertSame('You came in {rank, ordinal} place', (string) $types);
    }

    // -----------------------------------------------------------------------
    // Complex multi-variable message
    // -----------------------------------------------------------------------

    public function testComplexMessageWithAllTypes(): void
    {
        $types = new Types([
            Select::create('gender', [
                'male'   => [new Variable('name'), new Pattern(' received')],
                'female' => [new Variable('name'), new Pattern(' received')],
                'other'  => [new Variable('name'), new Pattern(' received')],
            ]),
            new Pattern(' '),
            Plural::create('count', [
                'one'   => [new Pattern('1 message')],
                'other' => [new Variable('#'), new Pattern(' messages')],
            ]),
            new Pattern(' on '),
            new Date('date', DateTimeFormat::Short),
        ]);

        $variants = $types->getAllVariants();
        // 3 gender × 2 plural = 6 variants
        self::assertCount(6, $variants);
        foreach ($variants as $v) {
            self::assertStringContainsString('{date, date, short}', (string) $v->types);
        }
    }
}
