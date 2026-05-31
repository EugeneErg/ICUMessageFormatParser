<?php

declare(strict_types=1);

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
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SkeletonTest extends TestCase
{
    private static function parse(string $skeleton): Skeleton
    {
        $tokens = (array) preg_split('/\\s+/', trim($skeleton));

        return Skeleton::createFromOptions(array_values(array_filter($tokens)));
    }

    private static function assertRoundtrip(string $input, string $expected, string $msg = ''): void
    {
        self::assertSame($expected, (string) self::parse($input), $msg ?: "Roundtrip: \"{$input}\"");
    }

    #[Test]
    public function defaultSkeletonIsEmpty(): void
    {
        $this->assertSame('', (string) new Skeleton());
    }

    #[Test]
    public function defaultPrecisionForDecimalIsFraction02(): void
    {
        $sk = new Skeleton();
        $this->assertInstanceOf(PrecisionFraction::class, $sk->precision);
        $p = $sk->precision;
        $this->assertSame(0, $p->minFraction);
        $this->assertSame(2, $p->maxFraction);
    }

    #[DataProvider('provideFormatRoundtripCases')]
    #[Test]
    public function formatRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideFormatRoundtripCases(): iterable
    {
        return [
            'decimal (default, no output)' => ['', ''],
            'integer' => ['integer', 'integer'],
            'percent' => ['percent', 'percent'],
            'permille' => ['permille', 'permille'],
            'base-unit' => ['base-unit', 'base-unit'],
            'scientific format' => ['scientific', '::scientific'],
            'currency USD' => ['currency/USD', 'currency/USD'],
            'currency EUR' => ['currency/EUR', 'currency/EUR'],
            'currency CAD' => ['currency/CAD', 'currency/CAD'],
            '%x100 concise' => ['%x100', '%x100'],
        ];
    }

    #[Test]
    public function formatEnumValues(): void
    {
        $this->assertSame('', Format::Decimal->value);
        $this->assertSame('integer', Format::Integer->value);
        $this->assertSame('percent', Format::Percent->value);
        $this->assertSame('permille', Format::Permille->value);
        $this->assertSame('base-unit', Format::BaseUnit->value);
        $this->assertSame('scientific', Format::Scientific->value);
    }

    #[Test]
    public function currencyDefaultIsUSD(): void
    {
        $c = new Currency();
        $this->assertSame('USD', $c->value);
    }

    #[Test]
    public function currencyWithUnitWidth(): void
    {
        self::assertRoundtrip('currency/CAD unit-width-narrow', '::currency/CAD unit-width-narrow');
        self::assertRoundtrip('currency/EUR unit-width-iso-code', '::currency/EUR unit-width-iso-code');
        self::assertRoundtrip('currency/JPY unit-width-full-name', '::currency/JPY unit-width-full-name');
    }

    #[Test]
    public function currencyDefaultPrecisionIsCurrencyStandard(): void
    {
        $sk = self::parse('currency/USD');
        $this->assertSame(Precision::CurrencyStandard, $sk->precision);
    }

    #[DataProvider('provideNotationRoundtripCases')]
    #[Test]
    public function notationRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideNotationRoundtripCases(): iterable
    {
        return [
            'standard → no output' => ['standard', ''],
            'notation-simple → explicit' => ['notation-simple', '::notation-simple'],
            'compact-short' => ['compact-short', '::compact-short'],
            'compact-long' => ['compact-long', '::compact-long'],
            'scientific' => ['scientific', '::scientific'],
            'engineering' => ['engineering', '::engineering'],
            'concise K → compact-short' => ['K', '::compact-short'],
            'concise KK → compact-long' => ['KK', '::compact-long'],
            'concise E0 → scientific' => ['E0', '::scientific'],
            'concise E00 → sci 2-digit exp' => ['E00', '::scientific/*ee'],
            'concise EE0 → engineering' => ['EE0', '::engineering'],
        ];
    }

    #[Test]
    public function bothStandardFormsAreDefault(): void
    {
        $byStandard = self::parse('standard');
        $bySimple = self::parse('notation-simple');
        $this->assertEquals(Notation::Standard, $byStandard->notation);
        $this->assertEquals(Notation::NotationSimple, $bySimple->notation);
        $this->assertSame('', (string) $byStandard);
        $this->assertSame('::notation-simple', (string) $bySimple);
    }

    #[DataProvider('provideScientificOptionsRoundtripCases')]
    #[Test]
    public function scientificOptionsRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideScientificOptionsRoundtripCases(): iterable
    {
        return [
            'scientific default' => ['scientific', '::scientific'],
            'scientific sign-always' => ['scientific/sign-always', '::scientific/sign-always'],
            'scientific 2-digit exp' => ['scientific/*ee', '::scientific/*ee'],
            'scientific sign-always 2-digit' => ['scientific/sign-always/*ee', '::scientific/sign-always/*ee'],
            'engineering sign-always' => ['engineering/sign-always', '::engineering/sign-always'],
            'E+! → scientific sign-always' => ['E+!0', '::scientific/sign-always'],
            'EE+!0 → engineering sign-always' => ['EE+!0', '::engineering/sign-always'],
        ];
    }

    #[Test]
    public function scientificOptionsDefaults(): void
    {
        $opts = new ScientificOptions();
        $this->assertNull($opts->exponentSign);
        $this->assertSame(1, $opts->minExponentDigits);
        $this->assertSame('', (string) $opts);
    }

    #[Test]
    public function scientificOptionsToString(): void
    {
        $opts = new ScientificOptions(Sign::Always, 2);
        $this->assertSame('/sign-always/*ee', (string) $opts);
    }

    #[DataProvider('provideSignRoundtripCases')]
    #[Test]
    public function signRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideSignRoundtripCases(): iterable
    {
        return [
            'auto (default, no output)' => ['sign-auto', ''],
            'always' => ['sign-always', '::sign-always'],
            'never' => ['sign-never', '::sign-never'],
            'accounting' => ['sign-accounting', '::sign-accounting'],
            'accounting-always' => ['sign-accounting-always', '::sign-accounting-always'],
            'except-zero' => ['sign-except-zero', '::sign-except-zero'],
            'accounting-except-zero' => ['sign-accounting-except-zero', '::sign-accounting-except-zero'],
            'negative' => ['sign-negative', '::sign-negative'],
            'accounting-negative' => ['sign-accounting-negative', '::sign-accounting-negative'],
            '+! → always' => ['+!', '::sign-always'],
            '+_ → never' => ['+_', '::sign-never'],
            '+? → except-zero' => ['+?', '::sign-except-zero'],
            '() → accounting' => ['()', '::sign-accounting'],
            '()! → accounting-always' => ['()!', '::sign-accounting-always'],
            '()? → accounting-except-zero' => ['()?', '::sign-accounting-except-zero'],
            '()- → accounting-negative' => ['()-', '::sign-accounting-negative'],
            '+- → negative' => ['+-', '::sign-negative'],
        ];
    }

    #[Test]
    public function signEnumValues(): void
    {
        $this->assertSame('sign-auto', Sign::Auto->value);
        $this->assertSame('sign-always', Sign::Always->value);
        $this->assertSame('sign-never', Sign::Never->value);
        $this->assertSame('sign-accounting', Sign::Accounting->value);
        $this->assertSame('sign-accounting-always', Sign::AccountingAlways->value);
        $this->assertSame('sign-except-zero', Sign::ExceptZero->value);
        $this->assertSame('sign-accounting-except-zero', Sign::AccountingExceptZero->value);
        $this->assertSame('sign-negative', Sign::Negative->value);
        $this->assertSame('sign-accounting-negative', Sign::AccountingNegative->value);
    }

    #[DataProvider('provideUnitWidthRoundtripCases')]
    #[Test]
    public function unitWidthRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideUnitWidthRoundtripCases(): iterable
    {
        return [
            'short (default, no output)' => ['unit-width-short', ''],
            'narrow' => ['unit-width-narrow', '::unit-width-narrow'],
            'full-name' => ['currency/USD unit-width-full-name', '::currency/USD unit-width-full-name'],
            'iso-code' => ['currency/USD unit-width-iso-code', '::currency/USD unit-width-iso-code'],
            'hidden' => ['currency/USD unit-width-hidden', '::currency/USD unit-width-hidden'],
        ];
    }

    #[DataProvider('provideNamedPrecisionRoundtripCases')]
    #[Test]
    public function namedPrecisionRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideNamedPrecisionRoundtripCases(): iterable
    {
        return [
            'precision-integer' => ['precision-integer', '::precision-integer'],
            'precision-unlimited' => ['precision-unlimited', '::precision-unlimited'],
            'precision-currency-standard' => ['currency/USD precision-currency-standard', 'currency/USD'],
            'precision-currency-cash' => ['currency/USD precision-currency-cash', '::currency/USD precision-currency-cash'],
        ];
    }

    #[Test]
    public function precisionEnumValues(): void
    {
        $this->assertSame('precision-integer', Precision::Integer->value);
        $this->assertSame('precision-unlimited', Precision::Unlimited->value);
        $this->assertSame('precision-currency-standard', Precision::CurrencyStandard->value);
        $this->assertSame('precision-currency-cash', Precision::CurrencyCash->value);
    }

    #[DataProvider('provideFractionPrecisionRoundtripCases')]
    #[Test]
    public function fractionPrecisionRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideFractionPrecisionRoundtripCases(): iterable
    {
        return [
            '.00 exact' => ['.00', '::.00'],
            '.0# min1 max2' => ['.0#', '::.0#'],
            '.## max2 (default → emits nothing for Decimal)' => ['.##', ''],
            '.00* unlimited' => ['.00*', '::.00*'],
            '.0 min1 max1' => ['.0', '::.0'],
            '.' => ['.', '::.'],
            '.00/w hide-if-whole' => ['.00/w', '::.00/w'],
            '.##/@@@* frac+sig' => ['.##/@@@*', '::.##/@@@*'],
            '.00/@## frac+sig' => ['.00/@##', '::.00/@##'],
        ];
    }

    #[Test]
    public function precisionFractionProperties(): void
    {
        $p = new PrecisionFraction(minFraction: 2, maxFraction: 4);
        $this->assertSame(2, $p->minFraction);
        $this->assertSame(4, $p->maxFraction);
        $this->assertFalse($p->trailingZeroHideIfWhole);
        $this->assertNull($p->minSignificantDigits);
        $this->assertNull($p->maxSignificantDigits);
        $this->assertSame('.00##', (string) $p);
    }

    #[Test]
    public function precisionFractionUnlimited(): void
    {
        $p = new PrecisionFraction(minFraction: 2, maxFraction: null);
        $this->assertNull($p->maxFraction);
        $this->assertSame('.00*', (string) $p);
    }

    #[Test]
    public function precisionFractionHideIfWhole(): void
    {
        $p = new PrecisionFraction(minFraction: 2, maxFraction: 2, trailingZeroHideIfWhole: true);
        $this->assertSame('.00/w', (string) $p);
    }

    #[Test]
    public function precisionFractionWithSignificantDigits(): void
    {
        $p = new PrecisionFraction(minFraction: 0, maxFraction: 2, minSignificantDigits: 3, maxSignificantDigits: null);
        $this->assertSame('.##/@@@*', (string) $p);
    }

    #[DataProvider('provideSignificantPrecisionRoundtripCases')]
    #[Test]
    public function significantPrecisionRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideSignificantPrecisionRoundtripCases(): iterable
    {
        return [
            '@@@  fixed 3' => ['@@@', '::@@@'],
            '@##  max 3 min 1' => ['@##', '::@##'],
            '@@#  min 2 max 3' => ['@@#', '::@@#'],
            '@@@* unlimited' => ['@@@*', '::@@@*'],
            '@    exactly 1' => ['@', '::@'],
            '@@   exactly 2' => ['@@', '::@@'],
            '@@@/w hide-if-whole' => ['@@@/w', '::@@@/w'],
        ];
    }

    #[Test]
    public function precisionSignificantProperties(): void
    {
        $p = new PrecisionSignificant(minDigits: 2, maxDigits: 4);
        $this->assertSame(2, $p->minDigits);
        $this->assertSame(4, $p->maxDigits);
        $this->assertFalse($p->trailingZeroHideIfWhole);
        $this->assertSame('@@##', (string) $p);
    }

    #[Test]
    public function precisionSignificantUnlimited(): void
    {
        $p = new PrecisionSignificant(minDigits: 3, maxDigits: null);
        $this->assertNull($p->maxDigits);
        $this->assertSame('@@@*', (string) $p);
    }

    #[Test]
    public function precisionSignificantHideIfWhole(): void
    {
        $p = new PrecisionSignificant(minDigits: 3, maxDigits: 3, trailingZeroHideIfWhole: true);
        $this->assertSame('@@@/w', (string) $p);
    }

    #[DataProvider('provideIncrementPrecisionRoundtripCases')]
    #[Test]
    public function incrementPrecisionRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    /**
     * @return array<int|string, string[]>
     */
    public static function provideIncrementPrecisionRoundtripCases(): iterable
    {
        return [
            '0.05' => ['precision-increment/0.05', '::precision-increment/0.05'],
            '0.5' => ['precision-increment/0.5', '::precision-increment/0.5'],
            '1' => ['precision-increment/1', '::precision-increment/1'],
            '50' => ['precision-increment/50', '::precision-increment/50'],
            '0.001' => ['precision-increment/0.001', '::precision-increment/0.001'],
        ];
    }

    #[Test]
    public function precisionIncrementProperties(): void
    {
        $p = new PrecisionIncrement(0.05);
        $this->assertEqualsWithDelta(0.05, $p->value, 0.0001);
    }

    #[DataProvider('provideGroupingRoundtripCases')]
    #[Test]
    public function groupingRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideGroupingRoundtripCases(): iterable
    {
        return [
            'auto (default, no output)' => ['group-auto', ''],
            'off' => ['group-off', '::group-off'],
            'min2' => ['group-min2', '::group-min2'],
            'on-aligned' => ['group-on-aligned', '::group-on-aligned'],
            'thousands' => ['group-thousands', '::group-thousands'],
            ',_ → off' => [',_', '::group-off'],
            ',? → min2' => [',?', '::group-min2'],
            ',! → on-aligned' => [',!', '::group-on-aligned'],
        ];
    }

    #[Test]
    public function groupingEnumValues(): void
    {
        $this->assertSame('group-off', Grouping::Off->value);
        $this->assertSame('group-min2', Grouping::Min2->value);
        $this->assertSame('group-auto', Grouping::Auto->value);
        $this->assertSame('group-on-aligned', Grouping::OnAligned->value);
        $this->assertSame('group-thousands', Grouping::Thousands->value);
    }

    #[DataProvider('provideScaleRoundtripCases')]
    #[Test]
    public function scaleRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideScaleRoundtripCases(): iterable
    {
        return [
            'scale 100' => ['scale/100', '::scale/100'],
            'scale 0.01' => ['scale/0.01', '::scale/0.01'],
            'scale 1000' => ['scale/1000', '::scale/1000'],
        ];
    }

    #[Test]
    public function scaleDefaultIsOne(): void
    {
        $sk = new Skeleton();
        $this->assertEqualsWithDelta(1.0, $sk->scale, 0.0001);
    }

    #[DataProvider('provideIntegerWidthRoundtripCases')]
    #[Test]
    public function integerWidthRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideIntegerWidthRoundtripCases(): iterable
    {
        return [
            'integer-width/*000 at-least-3' => ['integer-width/*000', '000'],
            'integer-width/##0  max-3' => ['integer-width/##0', '::integer-width/##0'],
            'integer-width/0    exactly-1' => ['integer-width/0', '::integer-width/0'],
            'integer-width/*    unlimited' => ['integer-width/*', '::integer-width/*'],
            'integer-width-trunc' => ['integer-width-trunc', '::integer-width-trunc'],
            'concise 000 → zeros=3' => ['000', '000'],
        ];
    }

    #[Test]
    public function integerWidthProperties(): void
    {
        $iw = new IntegerWidth(zeroFillTo: 3, truncateAt: null);
        $this->assertSame(3, $iw->zeroFillTo);
        $this->assertNull($iw->truncateAt);
        $this->assertSame('integer-width/*000', (string) $iw);
    }

    #[Test]
    public function integerWidthWithUpperBound(): void
    {
        $iw = new IntegerWidth(zeroFillTo: 1, truncateAt: 3);
        $this->assertSame('integer-width/##0', (string) $iw);
    }

    #[Test]
    public function integerWidthTrunc(): void
    {
        $iw = IntegerWidth::trunc();
        $this->assertSame(0, $iw->zeroFillTo);
        $this->assertSame(0, $iw->truncateAt);
        $this->assertSame('integer-width-trunc', (string) $iw);
    }

    #[Test]
    public function integerWidthFromConcise(): void
    {
        $iw = IntegerWidth::fromConcise(3);
        $this->assertSame(3, $iw->zeroFillTo);
        $this->assertNull($iw->truncateAt);
    }

    #[DataProvider('provideRoundingModeRoundtripCases')]
    #[Test]
    public function roundingModeRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideRoundingModeRoundtripCases(): iterable
    {
        return [
            'ceiling' => ['rounding-mode-ceiling', '::rounding-mode-ceiling'],
            'floor' => ['rounding-mode-floor', '::rounding-mode-floor'],
            'down' => ['rounding-mode-down', '::rounding-mode-down'],
            'up' => ['rounding-mode-up', '::rounding-mode-up'],
            'half-even' => ['rounding-mode-half-even', '::rounding-mode-half-even'],
            'half-down' => ['rounding-mode-half-down', '::rounding-mode-half-down'],
            'half-up' => ['rounding-mode-half-up', '::rounding-mode-half-up'],
            'unnecessary' => ['rounding-mode-unnecessary', '::rounding-mode-unnecessary'],
        ];
    }

    #[Test]
    public function roundingModeEnumValues(): void
    {
        $this->assertSame('rounding-mode-ceiling', RoundingMode::Ceiling->value);
        $this->assertSame('rounding-mode-floor', RoundingMode::Floor->value);
        $this->assertSame('rounding-mode-down', RoundingMode::Down->value);
        $this->assertSame('rounding-mode-up', RoundingMode::Up->value);
        $this->assertSame('rounding-mode-half-even', RoundingMode::HalfEven->value);
        $this->assertSame('rounding-mode-half-down', RoundingMode::HalfDown->value);
        $this->assertSame('rounding-mode-half-up', RoundingMode::HalfUp->value);
        $this->assertSame('rounding-mode-unnecessary', RoundingMode::Unnecessary->value);
    }

    #[DataProvider('provideDecimalSeparatorRoundtripCases')]
    #[Test]
    public function decimalSeparatorRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideDecimalSeparatorRoundtripCases(): iterable
    {
        return [
            'auto (default, no output)' => ['decimal-auto', ''],
            'always' => ['decimal-always', '::decimal-always'],
        ];
    }

    #[Test]
    public function decimalSeparatorEnumValues(): void
    {
        $this->assertSame('decimal-auto', DecimalSeparator::Auto->value);
        $this->assertSame('decimal-always', DecimalSeparator::Always->value);
    }

    #[DataProvider('provideNumberingSystemRoundtripCases')]
    #[Test]
    public function numberingSystemRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideNumberingSystemRoundtripCases(): iterable
    {
        return [
            'latin' => ['latin', '::latin'],
            'arabic-indic (arab)' => ['numbering-system/arab', '::numbering-system/arab'],
            'devanagari (deva)' => ['numbering-system/deva', '::numbering-system/deva'],
            'persian (arabext)' => ['numbering-system/arabext', '::numbering-system/arabext'],
        ];
    }

    #[Test]
    public function numberingSystemToString(): void
    {
        $this->assertSame('latin', (string) new NumberingSystem('latin'));
        $this->assertSame('numbering-system/arab', (string) new NumberingSystem('arab'));
    }

    #[DataProvider('provideMeasureUnitRoundtripCases')]
    #[Test]
    public function measureUnitRoundtrip(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideMeasureUnitRoundtripCases(): iterable
    {
        return [
            'length-meter' => ['measure-unit/length-meter', '::measure-unit/length-meter'],
            'duration-second' => ['measure-unit/duration-second', '::measure-unit/duration-second'],
            'measure-unit + per-measure-unit' => ['measure-unit/length-meter per-measure-unit/duration-second', '::measure-unit/length-meter per-measure-unit/duration-second'],
            'concise unit/meter' => ['unit/meter', '::measure-unit/meter'],
        ];
    }

    #[Test]
    public function measureUnitProperties(): void
    {
        $mu = new MeasureUnit('length-meter', 'duration-second');
        $this->assertSame('length-meter', $mu->unit);
        $this->assertSame('duration-second', $mu->perUnit);
        $this->assertSame('measure-unit/length-meter per-measure-unit/duration-second', (string) $mu);
    }

    #[Test]
    public function measureUnitWithoutPerUnit(): void
    {
        $mu = new MeasureUnit('length-meter');
        $this->assertNull($mu->perUnit);
        $this->assertSame('measure-unit/length-meter', (string) $mu);
    }

    #[DataProvider('provideDefaultPrecisionByFormatCases')]
    #[Test]
    public function defaultPrecisionByFormat(string $skeleton, string $expectedPrecision): void
    {
        $sk = self::parse($skeleton);
        $precision = $sk->precision;
        $precisionStr = $precision instanceof Precision ? $precision->value : (string) $precision;
        $this->assertSame($expectedPrecision, $precisionStr, "Default precision for: {$skeleton}");
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideDefaultPrecisionByFormatCases(): iterable
    {
        return [
            'decimal → .##' => ['', '.##'],
            'integer → precision-integer' => ['integer', Precision::Integer->value],
            'percent → precision-integer' => ['percent', Precision::Integer->value],
            'scientific → .000000' => ['scientific', '.000000'],
            'currency/USD → precision-currency-standard' => ['currency/USD', Precision::CurrencyStandard->value],
            'measure-unit → .##' => ['measure-unit/length-meter', '.##'],
        ];
    }

    #[DataProvider('provideCombinationsCases')]
    #[Test]
    public function combinations(string $input, string $expected): void
    {
        self::assertRoundtrip($input, $expected);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideCombinationsCases(): iterable
    {
        return [
            'percent + fraction' => ['percent .00', '::percent .00'],
            'currency + sign + width' => ['sign-always compact-short currency/GBP', '::currency/GBP compact-short sign-always'],
            'scientific + sign-always + 2-digit' => ['scientific/sign-always/*ee', '::scientific/sign-always/*ee'],
            'fraction + rounding' => ['.00 rounding-mode-half-up', '::.00 rounding-mode-half-up'],
            'measure + width + fraction' => ['measure-unit/length-meter unit-width-full-name .0#', '::measure-unit/length-meter unit-width-full-name .0#'],
            'currency + grouping-off' => ['currency/EUR group-off', '::currency/EUR group-off'],
            'scale + percent' => ['percent scale/100', '%x100'],
            'significant + rounding-mode' => ['@@@ rounding-mode-ceiling', '::@@@ rounding-mode-ceiling'],
            'integer-width + fraction' => ['integer-width/*00 .00', '::.00 00'],
            'numbering-system + sign' => ['latin sign-always', '::sign-always latin'],
        ];
    }

    #[Test]
    public function tryCreateFromPatternCurrency(): void
    {
        // 'currency' (no code) is a locale-aware named style: each locale uses its own
        // currency symbol (en_US→$, de_DE→€, ja_JP→¥). It is NOT equivalent to
        // '::currency/USD' which always formats as USD regardless of locale.
        $sk = Skeleton::tryCreateFromPattern(new Pattern('currency'));
        $this->assertNull($sk);
    }

    #[Test]
    public function tryCreateFromPatternFormat(): void
    {
        $sk = Skeleton::tryCreateFromPattern(new Pattern('integer'));
        $this->assertNotNull($sk);
        $this->assertSame(Format::Integer, $sk->format);
    }

    #[Test]
    public function tryCreateFromPatternPercent(): void
    {
        $sk = Skeleton::tryCreateFromPattern(new Pattern('percent'));
        $this->assertNotNull($sk);
        $this->assertSame(Format::Percent, $sk->format);
    }

    #[Test]
    public function tryCreateFromPatternZeros(): void
    {
        $sk = Skeleton::tryCreateFromPattern(new Pattern('000'));
        $this->assertNotNull($sk);
        $this->assertNotNull($sk->integerWidth);
        $this->assertSame(3, $sk->integerWidth->zeroFillTo);
        $this->assertNull($sk->integerWidth->truncateAt);
    }

    #[Test]
    public function tryCreateFromPatternUnknownReturnsNull(): void
    {
        $sk = Skeleton::tryCreateFromPattern(new Pattern('unknown-pattern'));
        $this->assertNull($sk);
    }

    #[Test]
    public function tryCreateFromPatternPercentX100(): void
    {
        $sk = Skeleton::tryCreateFromPattern(new Pattern('%x100'));
        $this->assertNotNull($sk);
        $this->assertSame(Format::Percent, $sk->format);
        $this->assertEqualsWithDelta(100.0, $sk->scale, 0.001);
    }

    #[Test]
    public function unknownTokenThrows(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/Unknown skeleton token/');
        self::parse('totally-unknown-token-xyz');
    }
}
