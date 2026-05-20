<?php

declare(strict_types = 1);

namespace Tests\DataTransferObjects\Number;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Currency;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\DecimalSeparator;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Format;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Grouping;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\IntegerWidth;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\MeasureUnit;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Notation;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\NumberingSystem;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Precision;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\PrecisionFraction;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\PrecisionIncrement;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\PrecisionSignificant;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\RoundingMode;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\ScientificOptions;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Sign;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Skeleton;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\UnitWidth;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use LogicException;
use PHPUnit\Framework\TestCase;

final class SkeletonTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private static function parse(string $skeleton): Skeleton
    {
        $tokens = preg_split('/\s+/', trim($skeleton));
        return Skeleton::createFromOptions(array_values(array_filter($tokens)));
    }

    private static function assertRoundtrip(string $input, string $expected, string $msg = ''): void
    {
        self::assertSame($expected, (string) self::parse($input), $msg ?: "Roundtrip: \"$input\"");
    }

    // -----------------------------------------------------------------------
    // Default skeleton
    // -----------------------------------------------------------------------

    public function testDefaultSkeletonIsEmpty(): void
    {
        self::assertSame('', (string) new Skeleton());
    }

    public function testDefaultPrecisionForDecimalIsFraction02(): void
    {
        $sk = new Skeleton();
        self::assertInstanceOf(PrecisionFraction::class, $sk->precision);
        /** @var PrecisionFraction $p */
        $p = $sk->precision;
        self::assertSame(0, $p->minFraction);
        self::assertSame(2, $p->maxFraction);
    }

    // -----------------------------------------------------------------------
    // Format
    // -----------------------------------------------------------------------

    /** @dataProvider formatProvider */
    public function testFormatRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    public static function formatProvider(): array
    {
        return [
            'decimal (default, no output)'  => ['',              ''],
            'integer'                        => ['integer',       'integer'],
            'percent'                        => ['percent',       'percent'],
            'permille'                       => ['permille',      'permille'],
            'base-unit'                      => ['base-unit',     'base-unit'],
            'scientific format'              => ['scientific',    '::scientific'],
            'currency USD → short form'      => ['currency/USD',  'currency'],
            'currency EUR'                   => ['currency/EUR',  'currency/EUR'],
            'currency CAD'                   => ['currency/CAD',  'currency/CAD'],
            '%x100 concise'                  => ['%x100',         '%x100'],   // percent + scale/100
        ];
    }

    public function testFormatEnumValues(): void
    {
        self::assertSame('', Format::Decimal->value);
        self::assertSame('integer', Format::Integer->value);
        self::assertSame('percent', Format::Percent->value);
        self::assertSame('permille', Format::Permille->value);
        self::assertSame('base-unit', Format::BaseUnit->value);
        self::assertSame('scientific', Format::Scientific->value);
    }

    // -----------------------------------------------------------------------
    // Currency
    // -----------------------------------------------------------------------

    public function testCurrencyDefaultIsUSD(): void
    {
        $c = new Currency();
        self::assertSame('USD', $c->value);
    }

    public function testCurrencyWithUnitWidth(): void
    {
        self::assertRoundtrip('currency/CAD unit-width-narrow', '::currency/CAD unit-width-narrow');
        self::assertRoundtrip('currency/EUR unit-width-iso-code', '::currency/EUR unit-width-iso-code');
        self::assertRoundtrip('currency/JPY unit-width-full-name', '::currency/JPY unit-width-full-name');
    }

    public function testCurrencyDefaultPrecisionIsCurrencyStandard(): void
    {
        $sk = self::parse('currency/USD');
        self::assertSame(Precision::CurrencyStandard, $sk->precision);
    }

    // -----------------------------------------------------------------------
    // Notation
    // -----------------------------------------------------------------------

    /** @dataProvider notationProvider */
    public function testNotationRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    public static function notationProvider(): array
    {
        return [
            'standard → no output'         => ['standard',       ''],
            'notation-simple → explicit'   => ['notation-simple','::notation-simple'],
            'compact-short'                => ['compact-short',  '::compact-short'],
            'compact-long'                 => ['compact-long',   '::compact-long'],
            'scientific'                   => ['scientific',     '::scientific'],
            'engineering'                  => ['engineering',    '::engineering'],
            // concise forms
            'concise K → compact-short'    => ['K',              '::compact-short'],
            'concise KK → compact-long'    => ['KK',             '::compact-long'],
            'concise E0 → scientific'      => ['E0',             '::scientific'],
            'concise E00 → sci 2-digit exp'=> ['E00',            '::scientific/*ee'],
            'concise EE0 → engineering'    => ['EE0',            '::engineering'],
        ];
    }

    public function testNotationEnumValues(): void
    {
        self::assertSame('standard',       Notation::Standard->value);
        self::assertSame('notation-simple',Notation::NotationSimple->value);
        self::assertSame('compact-short',  Notation::CompactShort->value);
        self::assertSame('compact-long',   Notation::CompactLong->value);
        self::assertSame('scientific',     Notation::Scientific->value);
        self::assertSame('engineering',    Notation::Engineering->value);
    }

    public function testBothStandardFormsAreDefault(): void
    {
        $byStandard = self::parse('standard');
        $bySimple   = self::parse('notation-simple');

        // Both mean "default notation" — neither should emit non-default tokens
        self::assertInstanceOf(\EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\StandardNotation::class, $byStandard->notation);
        self::assertInstanceOf(\EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\NotationSimple::class, $bySimple->notation);
        // Standard silently omitted; NotationSimple preserved explicitly
        self::assertSame('',                  (string) $byStandard);
        self::assertSame('::notation-simple', (string) $bySimple);
    }

    // -----------------------------------------------------------------------
    // ScientificOptions
    // -----------------------------------------------------------------------

    /** @dataProvider scientificOptionsProvider */
    public function testScientificOptionsRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    public static function scientificOptionsProvider(): array
    {
        return [
            'scientific default'                   => ['scientific',                    '::scientific'],
            'scientific sign-always'               => ['scientific/sign-always',        '::scientific/sign-always'],
            'scientific 2-digit exp'               => ['scientific/*ee',                '::scientific/*ee'],
            'scientific sign-always 2-digit'       => ['scientific/sign-always/*ee',    '::scientific/sign-always/*ee'],
            'engineering sign-always'              => ['engineering/sign-always',       '::engineering/sign-always'],
            // concise forms
            'E+! → scientific sign-always'         => ['E+!0',                          '::scientific/sign-always'],
            'EE+!0 → engineering sign-always'      => ['EE+!0',                         '::engineering/sign-always'],
        ];
    }

    public function testScientificOptionsDefaults(): void
    {
        $opts = new ScientificOptions();
        self::assertNull($opts->exponentSign);
        self::assertSame(1, $opts->minExponentDigits);
        self::assertSame('', (string) $opts);
    }

    public function testScientificOptionsToString(): void
    {
        $opts = new ScientificOptions(Sign::Always, 2);
        self::assertSame('/sign-always/*ee', (string) $opts);
    }

    // -----------------------------------------------------------------------
    // Sign
    // -----------------------------------------------------------------------

    /** @dataProvider signProvider */
    public function testSignRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    public static function signProvider(): array
    {
        return [
            'auto (default, no output)'     => ['sign-auto',                ''],
            'always'                        => ['sign-always',              '::sign-always'],
            'never'                         => ['sign-never',               '::sign-never'],
            'accounting'                    => ['sign-accounting',          '::sign-accounting'],
            'accounting-always'             => ['sign-accounting-always',   '::sign-accounting-always'],
            'except-zero'                   => ['sign-except-zero',         '::sign-except-zero'],
            'accounting-except-zero'        => ['sign-accounting-except-zero', '::sign-accounting-except-zero'],
            'negative'                      => ['sign-negative',            '::sign-negative'],
            'accounting-negative'           => ['sign-accounting-negative', '::sign-accounting-negative'],
            // concise forms
            '+! → always'                   => ['+!',   '::sign-always'],
            '+_ → never'                    => ['+_',   '::sign-never'],
            '+? → except-zero'              => ['+?',   '::sign-except-zero'],
            '() → accounting'               => ['()',   '::sign-accounting'],
            '()! → accounting-always'       => ['()!',  '::sign-accounting-always'],
            '()? → accounting-except-zero'  => ['()?',  '::sign-accounting-except-zero'],
            '()- → accounting-negative'     => ['()-',  '::sign-accounting-negative'],
            '+- → negative'                 => ['+-',   '::sign-negative'],
        ];
    }

    public function testSignEnumValues(): void
    {
        self::assertSame('sign-auto',                    Sign::Auto->value);
        self::assertSame('sign-always',                  Sign::Always->value);
        self::assertSame('sign-never',                   Sign::Never->value);
        self::assertSame('sign-accounting',              Sign::Accounting->value);
        self::assertSame('sign-accounting-always',       Sign::AccountingAlways->value);
        self::assertSame('sign-except-zero',             Sign::ExceptZero->value);
        self::assertSame('sign-accounting-except-zero',  Sign::AccountingExceptZero->value);
        self::assertSame('sign-negative',                Sign::Negative->value);
        self::assertSame('sign-accounting-negative',     Sign::AccountingNegative->value);
    }

    // -----------------------------------------------------------------------
    // UnitWidth
    // -----------------------------------------------------------------------

    /** @dataProvider unitWidthProvider */
    public function testUnitWidthRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    public static function unitWidthProvider(): array
    {
        return [
            'short (default, no output)' => ['unit-width-short',     ''],
            'narrow'                     => ['unit-width-narrow',     '::unit-width-narrow'],
            'full-name'                  => ['currency/USD unit-width-full-name',  '::currency/USD unit-width-full-name'],
            'iso-code'                   => ['currency/USD unit-width-iso-code',   '::currency/USD unit-width-iso-code'],
            'hidden'                     => ['currency/USD unit-width-hidden',     '::currency/USD unit-width-hidden'],
        ];
    }

    // -----------------------------------------------------------------------
    // Precision — named
    // -----------------------------------------------------------------------

    /** @dataProvider namedPrecisionProvider */
    public function testNamedPrecisionRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    public static function namedPrecisionProvider(): array
    {
        return [
            'precision-integer'            => ['precision-integer',          '::precision-integer'],
            'precision-unlimited'          => ['precision-unlimited',         '::precision-unlimited'],
            'precision-currency-standard'  => ['currency/USD precision-currency-standard', 'currency'],
            'precision-currency-cash'      => ['currency/USD precision-currency-cash', '::currency/USD precision-currency-cash'],
        ];
    }

    public function testPrecisionEnumValues(): void
    {
        self::assertSame('precision-integer',           Precision::Integer->value);
        self::assertSame('precision-unlimited',         Precision::Unlimited->value);
        self::assertSame('precision-currency-standard', Precision::CurrencyStandard->value);
        self::assertSame('precision-currency-cash',     Precision::CurrencyCash->value);
    }

    // -----------------------------------------------------------------------
    // Precision — fraction
    // -----------------------------------------------------------------------

    /** @dataProvider fractionPrecisionProvider */
    public function testFractionPrecisionRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    public static function fractionPrecisionProvider(): array
    {
        return [
            '.00 exact'             => ['.00',      '::.00'],
            '.0# min1 max2'         => ['.0#',      '::.0#'],
            '.## max2 (default → emits nothing for Decimal)' => ['.##', ''],
            '.00* unlimited'        => ['.00*',     '::.00*'],
            '.0 min1 max1'          => ['.0',       '::.0'],
            '.' => ['.',        '::.'],
            '.00/w hide-if-whole'   => ['.00/w',    '::.00/w'],
            '.##/@@@* frac+sig'     => ['.##/@@@*', '::.##/@@@*'],
            '.00/@## frac+sig'      => ['.00/@##',  '::.00/@##'],
        ];
    }

    public function testPrecisionFractionProperties(): void
    {
        $p = new PrecisionFraction(minFraction: 2, maxFraction: 4);
        self::assertSame(2, $p->minFraction);
        self::assertSame(4, $p->maxFraction);
        self::assertFalse($p->trailingZeroHideIfWhole);
        self::assertNull($p->minSignificantDigits);
        self::assertNull($p->maxSignificantDigits);
        self::assertSame('.00##', (string) $p);
    }

    public function testPrecisionFractionUnlimited(): void
    {
        $p = new PrecisionFraction(minFraction: 2, maxFraction: null);
        self::assertNull($p->maxFraction);
        self::assertSame('.00*', (string) $p);
    }

    public function testPrecisionFractionHideIfWhole(): void
    {
        $p = new PrecisionFraction(minFraction: 2, maxFraction: 2, trailingZeroHideIfWhole: true);
        self::assertSame('.00/w', (string) $p);
    }

    public function testPrecisionFractionWithSignificantDigits(): void
    {
        $p = new PrecisionFraction(
            minFraction: 0,
            maxFraction: 2,
            minSignificantDigits: 3,
            maxSignificantDigits: null,
        );
        self::assertSame('.##/@@@*', (string) $p);
    }

    // -----------------------------------------------------------------------
    // Precision — significant digits
    // -----------------------------------------------------------------------

    /** @dataProvider significantPrecisionProvider */
    public function testSignificantPrecisionRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    public static function significantPrecisionProvider(): array
    {
        return [
            '@@@  fixed 3'         => ['@@@',   '::@@@'],
            '@##  max 3 min 1'     => ['@##',   '::@##'],
            '@@#  min 2 max 3'     => ['@@#',   '::@@#'],
            '@@@* unlimited'       => ['@@@*',  '::@@@*'],
            '@    exactly 1'       => ['@',     '::@'],
            '@@   exactly 2'       => ['@@',    '::@@'],
            '@@@/w hide-if-whole'  => ['@@@/w', '::@@@/w'],
        ];
    }

    public function testPrecisionSignificantProperties(): void
    {
        $p = new PrecisionSignificant(minDigits: 2, maxDigits: 4);
        self::assertSame(2, $p->minDigits);
        self::assertSame(4, $p->maxDigits);
        self::assertFalse($p->trailingZeroHideIfWhole);
        self::assertSame('@@##', (string) $p);
    }

    public function testPrecisionSignificantUnlimited(): void
    {
        $p = new PrecisionSignificant(minDigits: 3, maxDigits: null);
        self::assertNull($p->maxDigits);
        self::assertSame('@@@*', (string) $p);
    }

    public function testPrecisionSignificantHideIfWhole(): void
    {
        $p = new PrecisionSignificant(minDigits: 3, maxDigits: 3, trailingZeroHideIfWhole: true);
        self::assertSame('@@@/w', (string) $p);
    }

    // -----------------------------------------------------------------------
    // Precision — increment
    // -----------------------------------------------------------------------

    /** @dataProvider incrementPrecisionProvider */
    public function testIncrementPrecisionRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    public static function incrementPrecisionProvider(): array
    {
        return [
            '0.05'  => ['precision-increment/0.05',  '::precision-increment/0.05'],
            '0.5'   => ['precision-increment/0.5',   '::precision-increment/0.5'],
            '1'     => ['precision-increment/1',     '::precision-increment/1'],
            '50'    => ['precision-increment/50',    '::precision-increment/50'],
            '0.001' => ['precision-increment/0.001', '::precision-increment/0.001'],
        ];
    }

    public function testPrecisionIncrementProperties(): void
    {
        $p = new PrecisionIncrement(0.05);
        self::assertEqualsWithDelta(0.05, $p->value, 0.0001);
    }

    // -----------------------------------------------------------------------
    // Grouping
    // -----------------------------------------------------------------------

    /** @dataProvider groupingProvider */
    public function testGroupingRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    public static function groupingProvider(): array
    {
        return [
            'auto (default, no output)' => ['group-auto',       ''],
            'off'                       => ['group-off',        '::group-off'],
            'min2'                      => ['group-min2',       '::group-min2'],
            'on-aligned'                => ['group-on-aligned', '::group-on-aligned'],
            'thousands'                 => ['group-thousands',  '::group-thousands'],
            // concise
            ',_ → off'                  => [',_',  '::group-off'],
            ',? → min2'                 => [',?',  '::group-min2'],
            ',! → on-aligned'           => [',!',  '::group-on-aligned'],
        ];
    }

    public function testGroupingEnumValues(): void
    {
        self::assertSame('group-off',        Grouping::Off->value);
        self::assertSame('group-min2',       Grouping::Min2->value);
        self::assertSame('group-auto',       Grouping::Auto->value);
        self::assertSame('group-on-aligned', Grouping::OnAligned->value);
        self::assertSame('group-thousands',  Grouping::Thousands->value);
    }

    // -----------------------------------------------------------------------
    // Scale
    // -----------------------------------------------------------------------

    /** @dataProvider scaleProvider */
    public function testScaleRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    public static function scaleProvider(): array
    {
        return [
            'scale 100'   => ['scale/100',  '::scale/100'],
            'scale 0.01'  => ['scale/0.01', '::scale/0.01'],
            'scale 1000'  => ['scale/1000', '::scale/1000'],
        ];
    }

    public function testScaleDefaultIsOne(): void
    {
        $sk = new Skeleton();
        self::assertEqualsWithDelta(1.0, $sk->scale, 0.0001);
    }

    // -----------------------------------------------------------------------
    // Integer width
    // -----------------------------------------------------------------------

    /** @dataProvider integerWidthProvider */
    public function testIntegerWidthRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    public static function integerWidthProvider(): array
    {
        return [
            'integer-width/*000 at-least-3' => ['integer-width/*000', '000'],
            'integer-width/##0  max-3'      => ['integer-width/##0',  '::integer-width/##0'],
            'integer-width/0    exactly-1'  => ['integer-width/0',    '::integer-width/0'],
            'integer-width/*    unlimited'  => ['integer-width/*',    '::integer-width/*'],
            'integer-width-trunc'           => ['integer-width-trunc','::integer-width-trunc'],
            'concise 000 → zeros=3'         => ['000',                '000'],
        ];
    }

    public function testIntegerWidthProperties(): void
    {
        $iw = new IntegerWidth(zeroFillTo: 3, truncateAt: null);
        self::assertSame(3, $iw->zeroFillTo);
        self::assertNull($iw->truncateAt);
        self::assertSame('integer-width/*000', (string) $iw);
    }

    public function testIntegerWidthWithUpperBound(): void
    {
        $iw = new IntegerWidth(zeroFillTo: 1, truncateAt: 3);
        self::assertSame('integer-width/##0', (string) $iw);
    }

    public function testIntegerWidthTrunc(): void
    {
        $iw = IntegerWidth::trunc();
        self::assertSame(0, $iw->zeroFillTo);
        self::assertSame(0, $iw->truncateAt);
        self::assertSame('integer-width-trunc', (string) $iw);
    }

    public function testIntegerWidthFromConcise(): void
    {
        $iw = IntegerWidth::fromConcise(3);
        self::assertSame(3, $iw->zeroFillTo);
        self::assertNull($iw->truncateAt);
    }

    // -----------------------------------------------------------------------
    // Rounding mode
    // -----------------------------------------------------------------------

    /** @dataProvider roundingModeProvider */
    public function testRoundingModeRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    public static function roundingModeProvider(): array
    {
        return [
            'ceiling'     => ['rounding-mode-ceiling',     '::rounding-mode-ceiling'],
            'floor'       => ['rounding-mode-floor',       '::rounding-mode-floor'],
            'down'        => ['rounding-mode-down',        '::rounding-mode-down'],
            'up'          => ['rounding-mode-up',          '::rounding-mode-up'],
            'half-even'   => ['rounding-mode-half-even',   '::rounding-mode-half-even'],
            'half-down'   => ['rounding-mode-half-down',   '::rounding-mode-half-down'],
            'half-up'     => ['rounding-mode-half-up',     '::rounding-mode-half-up'],
            'unnecessary' => ['rounding-mode-unnecessary', '::rounding-mode-unnecessary'],
        ];
    }

    public function testRoundingModeEnumValues(): void
    {
        self::assertSame('rounding-mode-ceiling',     RoundingMode::Ceiling->value);
        self::assertSame('rounding-mode-floor',       RoundingMode::Floor->value);
        self::assertSame('rounding-mode-down',        RoundingMode::Down->value);
        self::assertSame('rounding-mode-up',          RoundingMode::Up->value);
        self::assertSame('rounding-mode-half-even',   RoundingMode::HalfEven->value);
        self::assertSame('rounding-mode-half-down',   RoundingMode::HalfDown->value);
        self::assertSame('rounding-mode-half-up',     RoundingMode::HalfUp->value);
        self::assertSame('rounding-mode-unnecessary', RoundingMode::Unnecessary->value);
    }

    // -----------------------------------------------------------------------
    // Decimal separator
    // -----------------------------------------------------------------------

    /** @dataProvider decimalSeparatorProvider */
    public function testDecimalSeparatorRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    public static function decimalSeparatorProvider(): array
    {
        return [
            'auto (default, no output)' => ['decimal-auto',   ''],
            'always'                    => ['decimal-always',  '::decimal-always'],
        ];
    }

    public function testDecimalSeparatorEnumValues(): void
    {
        self::assertSame('decimal-auto',   DecimalSeparator::Auto->value);
        self::assertSame('decimal-always', DecimalSeparator::Always->value);
    }

    // -----------------------------------------------------------------------
    // Numbering system
    // -----------------------------------------------------------------------

    /** @dataProvider numberingSystemProvider */
    public function testNumberingSystemRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    public static function numberingSystemProvider(): array
    {
        return [
            'latin'                => ['latin',                   '::latin'],
            'arabic-indic (arab)'  => ['numbering-system/arab',   '::numbering-system/arab'],
            'devanagari (deva)'    => ['numbering-system/deva',   '::numbering-system/deva'],
            'persian (arabext)'    => ['numbering-system/arabext','::numbering-system/arabext'],
        ];
    }

    public function testNumberingSystemToString(): void
    {
        self::assertSame('latin',                   (string) new NumberingSystem('latin'));
        self::assertSame('numbering-system/arab',   (string) new NumberingSystem('arab'));
    }

    // -----------------------------------------------------------------------
    // Measure unit
    // -----------------------------------------------------------------------

    /** @dataProvider measureUnitProvider */
    public function testMeasureUnitRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    public static function measureUnitProvider(): array
    {
        return [
            'length-meter'                         => ['measure-unit/length-meter',                           '::measure-unit/length-meter'],
            'duration-second'                      => ['measure-unit/duration-second',                        '::measure-unit/duration-second'],
            'measure-unit + per-measure-unit'      => ['measure-unit/length-meter per-measure-unit/duration-second', '::measure-unit/length-meter per-measure-unit/duration-second'],
            'concise unit/meter'                   => ['unit/meter',                                          '::measure-unit/meter'],
        ];
    }

    public function testMeasureUnitProperties(): void
    {
        $mu = new MeasureUnit('length-meter', 'duration-second');
        self::assertSame('length-meter',   $mu->unit);
        self::assertSame('duration-second',$mu->perUnit);
        self::assertSame('measure-unit/length-meter per-measure-unit/duration-second', (string) $mu);
    }

    public function testMeasureUnitWithoutPerUnit(): void
    {
        $mu = new MeasureUnit('length-meter');
        self::assertNull($mu->perUnit);
        self::assertSame('measure-unit/length-meter', (string) $mu);
    }

    // -----------------------------------------------------------------------
    // Default precision by format
    // -----------------------------------------------------------------------

    /** @dataProvider defaultPrecisionProvider */
    public function testDefaultPrecisionByFormat(string $skeleton, string $expectedPrecision): void
    {
        $sk = self::parse($skeleton);
        $precision = $sk->precision;
        $precisionStr = $precision instanceof Precision ? $precision->value : (string) $precision;
        self::assertSame($expectedPrecision, $precisionStr, "Default precision for: $skeleton");
    }

    public static function defaultPrecisionProvider(): array
    {
        return [
            'decimal → .##'                          => ['',               '.##'],
            'integer → precision-integer'            => ['integer',        Precision::Integer->value],
            'percent → precision-integer'            => ['percent',        Precision::Integer->value],
            'scientific → .000000'                   => ['scientific',     '.000000'],
            'currency/USD → precision-currency-standard' => ['currency/USD', Precision::CurrencyStandard->value],
            'measure-unit → .##'                     => ['measure-unit/length-meter', '.##'],
        ];
    }

    // -----------------------------------------------------------------------
    // Multi-token combinations
    // -----------------------------------------------------------------------

    /** @dataProvider combinationProvider */
    public function testCombinations(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    public static function combinationProvider(): array
    {
        return [
            'percent + fraction'                  => ['percent .00',                         '::percent .00'],
            'currency + sign + width'             => ['sign-always compact-short currency/GBP','::currency/GBP compact-short sign-always'],
            'scientific + sign-always + 2-digit'  => ['scientific/sign-always/*ee',           '::scientific/sign-always/*ee'],
            'fraction + rounding'                 => ['.00 rounding-mode-half-up',            '::.00 rounding-mode-half-up'],
            'measure + width + fraction'          => ['measure-unit/length-meter unit-width-full-name .0#', '::measure-unit/length-meter unit-width-full-name .0#'],
            'currency + grouping-off'             => ['currency/EUR group-off',               '::currency/EUR group-off'],
            'scale + percent'                     => ['percent scale/100',                    '%x100'],
            'significant + rounding-mode'         => ['@@@ rounding-mode-ceiling',            '::@@@ rounding-mode-ceiling'],
            'integer-width + fraction'            => ['integer-width/*00 .00',               '::.00 00'],
            'numbering-system + sign'             => ['latin sign-always',                   '::sign-always latin'],
        ];
    }

    // -----------------------------------------------------------------------
    // tryCreateFromPattern
    // -----------------------------------------------------------------------

    public function testTryCreateFromPatternCurrency(): void
    {
        $sk = Skeleton::tryCreateFromPattern(new Pattern('currency'));
        self::assertNotNull($sk);
        self::assertInstanceOf(Currency::class, $sk->format);
        self::assertSame('USD', $sk->format->value);
    }

    public function testTryCreateFromPatternFormat(): void
    {
        $sk = Skeleton::tryCreateFromPattern(new Pattern('integer'));
        self::assertNotNull($sk);
        self::assertSame(Format::Integer, $sk->format);
    }

    public function testTryCreateFromPatternPercent(): void
    {
        $sk = Skeleton::tryCreateFromPattern(new Pattern('percent'));
        self::assertNotNull($sk);
        self::assertSame(Format::Percent, $sk->format);
    }

    public function testTryCreateFromPatternZeros(): void
    {
        $sk = Skeleton::tryCreateFromPattern(new Pattern('000'));
        self::assertNotNull($sk);
        self::assertNotNull($sk->integerWidth);
        self::assertSame(3, $sk->integerWidth->zeroFillTo);
        self::assertNull($sk->integerWidth->truncateAt);
    }

    public function testTryCreateFromPatternUnknownReturnsNull(): void
    {
        $sk = Skeleton::tryCreateFromPattern(new Pattern('unknown-pattern'));
        self::assertNull($sk);
    }

    public function testTryCreateFromPatternPercentX100(): void
    {
        $sk = Skeleton::tryCreateFromPattern(new Pattern('%x100'));
        self::assertNotNull($sk);
        self::assertSame(Format::Percent, $sk->format);
        self::assertEqualsWithDelta(100.0, $sk->scale, 0.001);
    }

    // -----------------------------------------------------------------------
    // Error handling
    // -----------------------------------------------------------------------

    public function testUnknownTokenThrows(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Unknown skeleton token/');
        self::parse('totally-unknown-token-xyz');
    }
}