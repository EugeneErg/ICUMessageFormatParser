<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use InvalidArgumentException;
use LogicException;
use Stringable;

use function array_slice;
use function count;
use function strlen;

/**
 * ICU Number Skeleton.
 *
 * All invalid states are prevented either by the type system or by constructor
 * validation with InvalidArgumentException.
 *
 * Structural guarantees (type system):
 *   - ScientificOptions can only exist inside ScientificNotation / EngineeringNotation
 *   - integerWidth covers the "zeros" shorthand — no duplicate field
 *   - percentScale is derived, not stored — no desync possible
 *
 * Runtime validation (constructor):
 *   - Numeric ranges (minFraction >= 0, etc.) — see each sub-DTO
 *   - Cross-field rules in Skeleton (unitWidth compat, currency precision compat)
 *
 * @see https://unicode-org.github.io/icu/userguide/format_parse/numbers/skeletons.html
 */
final readonly class Skeleton implements Stringable
{
    public Precision|PrecisionFraction|PrecisionSignificant|PrecisionIncrement $precision;

    public function __construct(
        public Format|Currency|MeasureUnit $format = Format::Decimal,
        public NumberNotation|Notation $notation = new StandardNotation(),
        public Sign $sign = Sign::Auto,
        public UnitWidth $unitWidth = UnitWidth::Short,
        Precision|PrecisionFraction|PrecisionSignificant|PrecisionIncrement|null $precision = null,
        public Grouping $grouping = Grouping::Auto,
        public float $scale = 1.0,
        public IntegerWidth|null $integerWidth = null,
        public RoundingMode|null $roundingMode = null,
        public DecimalSeparator $decimalSeparator = DecimalSeparator::Auto,
        public NumberingSystem|null $numberingSystem = null,
    ) {
        if ($scale === 0.0) {
            throw new InvalidArgumentException('Skeleton: scale must not be zero.');
        }

        $unitWidthRequiresCurrency = $unitWidth === UnitWidth::IsoCode || $unitWidth === UnitWidth::Hidden;
        $unitWidthRequiresCurrencyOrMeasure = $unitWidth === UnitWidth::FullName;

        if ($unitWidthRequiresCurrency && !($format instanceof Currency)) {
            throw new InvalidArgumentException("Skeleton: {$unitWidth->value} is only valid with a Currency format.");
        }

        if ($unitWidthRequiresCurrencyOrMeasure && !($format instanceof Currency) && !($format instanceof MeasureUnit)) {
            throw new InvalidArgumentException('Skeleton: unit-width-full-name is only valid with Currency or MeasureUnit format.');
        }

        if ($precision === Precision::CurrencyStandard || $precision === Precision::CurrencyCash && !$format instanceof Currency) {
            throw new InvalidArgumentException('Skeleton: precision-currency-* is only valid with a Currency format.');
        }

        $this->precision = $precision ?? $this->defaultPrecision();
    }

    // ------------------------------------------------------------------
    // Serialise
    // ------------------------------------------------------------------

    public function __toString(): string
    {
        $tokens = [];
        $canBeSimple = true;

        // --- format ---
        if ($this->format instanceof Currency) {
            $tokens[] = 'currency/' . $this->format->value;
        } elseif ($this->format instanceof MeasureUnit) {
            $tokens[] = 'measure-unit/' . $this->format->unit;

            if ($this->format->perUnit !== null) {
                $tokens[] = 'per-measure-unit/' . $this->format->perUnit;
            }

            $canBeSimple = false;
        } elseif ($this->format !== Format::Decimal) {
            $tokens[] = $this->format->value;
        }

        // --- notation ---
        $notationStr = (string) $this->notation;

        if ($notationStr !== '') {
            $tokens[] = $notationStr;
            $canBeSimple = false;
        }

        if ($this->sign !== Sign::Auto) {
            $tokens[] = $this->sign->value;
            $canBeSimple = false;
        }

        if ($this->unitWidth !== UnitWidth::Short) {
            $tokens[] = $this->unitWidth->value;
            $canBeSimple = false;
        }

        $precToken = $this->precisionToken($this->precision);
        $defaultPrecToken = $this->precisionToken($this->defaultPrecision());

        if ($precToken !== $defaultPrecToken) {
            $tokens[] = $precToken;
            $canBeSimple = false;
        }

        if ($this->grouping !== Grouping::Auto) {
            $tokens[] = $this->grouping->value;
            $canBeSimple = false;
        }

        if ($this->format === Format::Percent && $this->scale === 100.0) {
            $idx = array_search('percent', $tokens, true);

            if ($idx !== false) {
                $tokens[$idx] = '%x100';
                $tokens = array_values($tokens);
            }
        } elseif ($this->scale !== 1.0) {
            $tokens[] = 'scale/' . self::formatFloat($this->scale);
            $canBeSimple = false;
        }

        if ($this->integerWidth !== null) {
            if ($this->integerWidth->truncateAt === null && $this->integerWidth->zeroFillTo > 0) {
                $tokens[] = str_repeat('0', $this->integerWidth->zeroFillTo);
            } else {
                $tokens[] = (string) $this->integerWidth;
                $canBeSimple = false;
            }
        }

        if ($this->roundingMode !== null) {
            $tokens[] = $this->roundingMode->value;
            $canBeSimple = false;
        }

        if ($this->decimalSeparator !== DecimalSeparator::Auto) {
            $tokens[] = $this->decimalSeparator->value;
            $canBeSimple = false;
        }

        if ($this->numberingSystem !== null) {
            $tokens[] = (string) $this->numberingSystem;
            $canBeSimple = false;
        }

        if ($tokens === []) {
            return '';
        }

        if ($canBeSimple && count($tokens) === 1) {
            if ($tokens[0] === 'currency/USD') {
                return 'currency';
            }

            return $tokens[0];
        }

        return '::' . implode(' ', $tokens);
    }

    // ------------------------------------------------------------------
    // Factory helpers
    // ------------------------------------------------------------------

    /**
     * @param array<int, string> $tokens skeleton tokens with '::' already stripped
     */
    public static function createFromOptions(array $tokens): self
    {
        $args = [];

        foreach ($tokens as $token) {
            $token = trim($token);

            if ($token === '') {
                continue;
            }

            self::applyToken($token, $args);
        }

        return new self(...$args);
    }

    public static function tryCreateFromPattern(Pattern $pattern): self|null
    {
        $option = trim($pattern->value);

        if ($option === 'currency') {
            return new self(new Currency());
        }

        if ($option === '%x100') {
            return new self(format: Format::Percent, scale: 100.0);
        }

        $format = Format::tryFrom($option);

        if ($format !== null) {
            return new self($format);
        }

        $zeros = self::parseZeros($option);

        if ($zeros !== null) {
            return new self(integerWidth: IntegerWidth::fromConcise($zeros));
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Token parser
    // ------------------------------------------------------------------

    /**
     * @param array<string, mixed> $args
     */
    private static function applyToken(string $token, array &$args): void
    {
        if ($token === 'notation-simple') {
            $args['notation'] = new NotationSimple();

            return;
        }

        if ($token === 'standard') {
            $args['notation'] = new StandardNotation();

            return;
        }

        if ($token === 'K') {
            $args['notation'] = new CompactShortNotation();

            return;
        }

        if ($token === 'KK') {
            $args['notation'] = new CompactLongNotation();

            return;
        }

        if ($token === 'compact-short') {
            $args['notation'] = new CompactShortNotation();

            return;
        }

        if ($token === 'compact-long') {
            $args['notation'] = new CompactLongNotation();

            return;
        }

        // Concise scientific/engineering: E0, E00, EE+!0, E+?00 …
        if (preg_match('/\\A(EE?)((?:[+!?]|[+][!?])?)(0+)\\z/', $token, $m)) {
            $isEngineering = $m[1] === 'EE';
            $sciSign = self::parseConciseSign($m[2]);
            $minExp = strlen($m[3]);
            $opts = new ScientificOptions(exponentSign: $sciSign, minExponentDigits: $minExp);
            $args['notation'] = $isEngineering
                ? new EngineeringNotation($opts)
                : new ScientificNotation($opts);

            return;
        }

        // Long-form scientific/engineering with options
        if (str_starts_with($token, 'scientific') || str_starts_with($token, 'engineering')) {
            $parts = explode('/', $token);
            $stem = array_shift($parts);
            $isEngineering = $stem === 'engineering';
            $sciSign = null;
            $minExp = 1;

            foreach ($parts as $opt) {
                if (str_starts_with($opt, 'sign-')) {
                    $sciSign = Sign::tryFrom($opt);
                } elseif (preg_match('/\\A[*+](e+)\\z/', $opt, $m)) {
                    $minExp = strlen($m[1]);
                }
            }

            $opts = ($sciSign !== null || $minExp > 1)
                ? new ScientificOptions(exponentSign: $sciSign, minExponentDigits: $minExp)
                : null;
            $args['notation'] = $isEngineering
                ? new EngineeringNotation($opts ?? new ScientificOptions())
                : new ScientificNotation($opts);

            return;
        }

        if ($token === '%x100') {
            $args['format'] = Format::Percent;
            $args['scale'] = 100.0;

            return;
        }

        if ($token === '%') {
            $args['format'] = Format::Percent;

            return;
        }

        if (str_starts_with($token, 'currency/')) {
            $args['format'] = new Currency(substr($token, 9));

            return;
        }

        if (str_starts_with($token, 'measure-unit/')) {
            $existing = $args['format'] ?? Format::Decimal;
            $args['format'] = new MeasureUnit(unit: substr($token, 13), perUnit: $existing instanceof MeasureUnit ? $existing->perUnit : null);

            return;
        }

        if (str_starts_with($token, 'per-measure-unit/')) {
            $existing = $args['format'] ?? Format::Decimal;
            $args['format'] = new MeasureUnit(unit: $existing instanceof MeasureUnit ? $existing->unit : '', perUnit: substr($token, 17));

            return;
        }

        if (str_starts_with($token, 'unit/')) {
            $args['format'] = new MeasureUnit(unit: substr($token, 5));

            return;
        }

        $format = Format::tryFrom($token);

        if ($format !== null) {
            $args['format'] = $format;

            return;
        }

        $conciseSign = self::parseConciseSign($token);

        if ($conciseSign !== null) {
            $args['sign'] = $conciseSign;

            return;
        }

        $sign = Sign::tryFrom($token);

        if ($sign !== null) {
            $args['sign'] = $sign;

            return;
        }

        $unitWidth = UnitWidth::tryFrom($token);

        if ($unitWidth !== null) {
            $args['unitWidth'] = $unitWidth;

            return;
        }

        if ($token === 'precision-unlimited') {
            $args['precision'] = Precision::Unlimited;

            return;
        }

        if ($token === 'precision-integer' || $token === 'precision-integer/w') {
            $args['precision'] = str_ends_with($token, '/w') ? new PrecisionFraction(0, 0, true) : Precision::Integer;

            return;
        }

        $namedPrecision = Precision::tryFrom($token);

        if ($namedPrecision !== null) {
            $args['precision'] = $namedPrecision;

            return;
        }

        if (str_starts_with($token, 'precision-increment/')) {
            $args['precision'] = new PrecisionIncrement((float) substr($token, 20));

            return;
        }

        if (str_starts_with($token, '.')) {
            $args['precision'] = self::parseFractionPrecision($token);

            return;
        }

        if ($token === '.') {
            $args['precision'] = new PrecisionFraction(0, 0);

            return;
        }

        if (str_starts_with($token, '@')) {
            $args['precision'] = self::parseSignificantDigits($token);

            return;
        }

        $roundingMode = RoundingMode::tryFrom($token);

        if ($roundingMode !== null) {
            $args['roundingMode'] = $roundingMode;

            return;
        }

        $conciseGrouping = match ($token) {
            ',_' => Grouping::Off,
            ',?' => Grouping::Min2,
            ',!' => Grouping::OnAligned,
            default => null,
        };

        if ($conciseGrouping !== null) {
            $args['grouping'] = $conciseGrouping;

            return;
        }

        $grouping = Grouping::tryFrom($token);

        if ($grouping !== null) {
            $args['grouping'] = $grouping;

            return;
        }

        // ---- Scale ----
        if (str_starts_with($token, 'scale/')) {
            $args['scale'] = (float) substr($token, 6);

            return;
        }

        // ---- IntegerWidth ----
        if ($token === 'integer-width-trunc') {
            $args['integerWidth'] = IntegerWidth::trunc();

            return;
        }

        if (str_starts_with($token, 'integer-width/')) {
            $args['integerWidth'] = self::parseIntegerWidth(substr($token, 14));

            return;
        }

        if (str_starts_with($token, 'integer-width/*')) {
            $zeros = substr($token, 15);
            $args['integerWidth'] = IntegerWidth::fromConcise(strlen($zeros));

            return;
        }

        $zeros = self::parseZeros($token);

        if ($zeros !== null) {
            $args['integerWidth'] = IntegerWidth::fromConcise($zeros);

            return;
        }

        $decimal = DecimalSeparator::tryFrom($token);

        if ($decimal !== null) {
            $args['decimalSeparator'] = $decimal;

            return;
        }

        if ($token === 'latin') {
            $args['numberingSystem'] = new NumberingSystem('latin');

            return;
        }

        if (str_starts_with($token, 'numbering-system/')) {
            $args['numberingSystem'] = new NumberingSystem(substr($token, 17));

            return;
        }

        throw new LogicException('Unknown skeleton token: "' . $token . '"');
    }

    // ------------------------------------------------------------------
    // Precision helpers
    // ------------------------------------------------------------------

    private static function parseFractionPrecision(string $token): PrecisionFraction
    {
        $parts = explode('/', $token, 3);
        $stem = $parts[0];
        $body = substr($stem, 1);
        preg_match('/\\A(0*)([#]*)(\\*)?\\z/', $body, $m);
        $minFraction = strlen($m[1]);
        $unlimited = isset($m[3]) && $m[3] === '*';
        $maxFraction = $unlimited ? null : ($minFraction + strlen($m[2]));
        $minSig = null;
        $maxSig = null;
        $sigMode = null;
        $hideIfWhole = false;

        foreach (array_slice($parts, 1) as $opt) {
            if ($opt === 'w') {
                $hideIfWhole = true;
            } elseif (preg_match('/\\A(@+)([#]*)(\\*|[rs])?\\z/', $opt, $sm)) {
                $minSig = strlen($sm[1]);
                $wildcard = isset($sm[3]) && $sm[3] === '*';
                $maxSig = $wildcard ? null : ($minSig + strlen($sm[2]));
                $mode = $sm[3] ?? null;
                $sigMode = ($mode === 's' || $mode === 'r') ? $mode : null;
            }
        }

        return new PrecisionFraction(
            minFraction: $minFraction,
            maxFraction: $maxFraction,
            trailingZeroHideIfWhole: $hideIfWhole,
            minSignificantDigits: $minSig,
            maxSignificantDigits: $maxSig,
            significantDigitsMode: $sigMode,
        );
    }

    private static function parseSignificantDigits(string $token): PrecisionSignificant
    {
        $parts = explode('/', $token, 2);
        $stem = $parts[0];
        $hideIfWhole = isset($parts[1]) && $parts[1] === 'w';
        preg_match('/\\A(@+)([#]*)(\\*)?\\z/', $stem, $m);
        $minDigits = strlen($m[1]);
        $unlimited = isset($m[3]) && $m[3] === '*';
        $maxDigits = $unlimited ? null : ($minDigits + strlen($m[2]));

        return new PrecisionSignificant(
            minDigits: $minDigits,
            maxDigits: $maxDigits,
            trailingZeroHideIfWhole: $hideIfWhole,
        );
    }

    private static function parseIntegerWidth(string $option): IntegerWidth
    {
        if (str_starts_with($option, '*')) {
            return new IntegerWidth(zeroFillTo: strlen(substr($option, 1)), truncateAt: null);
        }

        preg_match('/\\A(#*)(0*)\\z/', $option, $m);

        return new IntegerWidth(zeroFillTo: strlen($m[2]), truncateAt: strlen($m[2]) + strlen($m[1]));
    }

    private static function parseConciseSign(string $token): Sign|null
    {
        return match ($token) {
            '+!' => Sign::Always,
            '+_' => Sign::Never,
            '+?' => Sign::ExceptZero,
            '()' => Sign::Accounting,
            '()!' => Sign::AccountingAlways,
            '()?' => Sign::AccountingExceptZero,
            '()-' => Sign::AccountingNegative,
            '+-' => Sign::Negative,
            default => null,
        };
    }

    private static function parseZeros(string $token): int|null
    {
        return preg_match('/\\A0+\\z/', $token) ? strlen($token) : null;
    }

    private static function formatFloat(float $value): string
    {
        $str = rtrim(number_format($value, 10, '.', ''), '0');

        return rtrim($str, '.') ?: '0';
    }

    // ------------------------------------------------------------------
    // Precision serialisation
    // ------------------------------------------------------------------

    private function precisionToken(
        Precision|PrecisionFraction|PrecisionSignificant|PrecisionIncrement $precision,
    ): string {
        if ($precision instanceof Precision) {
            return $precision->value;
        }

        if ($precision instanceof PrecisionIncrement) {
            return 'precision-increment/' . self::formatFloat($precision->value);
        }

        return (string) $precision;
    }

    // ------------------------------------------------------------------
    // Default precision
    // ------------------------------------------------------------------

    private function defaultPrecision(): Precision|PrecisionFraction
    {
        if ($this->format instanceof Currency) {
            return Precision::CurrencyStandard;
        }

        if ($this->format instanceof MeasureUnit) {
            return new PrecisionFraction(minFraction: 0, maxFraction: 2);
        }

        if ($this->notation instanceof ScientificNotation || $this->notation instanceof EngineeringNotation) {
            return new PrecisionFraction(minFraction: 6, maxFraction: 6);
        }

        return match ($this->format) {
            Format::Percent,
            Format::Integer => Precision::Integer,
            default => new PrecisionFraction(minFraction: 0, maxFraction: 2),
        };
    }
}
