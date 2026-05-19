<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use LogicException;
use Stringable;

/**
 * ICU Number Skeleton.
 *
 * Supports the full long-form skeleton syntax plus common concise forms.
 *
 * @see https://unicode-org.github.io/icu/userguide/format_parse/numbers/skeletons.html
 */
final readonly class Skeleton implements Stringable
{
    /**
     * The active precision setting. One of:
     *   - Precision          (precision-integer, precision-currency-*, precision-unlimited)
     *   - PrecisionFraction  (.00, .##, .0#, .00*, …)
     *   - PrecisionSignificant (@@@, @##, …)
     *   - PrecisionIncrement (precision-increment/0.05)
     */
    public Precision|PrecisionFraction|PrecisionSignificant|PrecisionIncrement $precision;

    public function __construct(
        /** Unit: Format enum, Currency, or MeasureUnit. */
        public Format|Currency|MeasureUnit $format = Format::Decimal,

        /** Notation. For scientific/engineering, see $scientificOptions. */
        public Notation $notation = Notation::Standard,

        /** Additional options when notation is Scientific or Engineering. */
        public ?ScientificOptions $scientificOptions = null,

        /** Sign display. */
        public Sign $sign = Sign::Auto,

        /** Unit width. */
        public UnitWidth $unitWidth = UnitWidth::Short,

        /** Precision — resolved in constructor when null. */
        Precision|PrecisionFraction|PrecisionSignificant|PrecisionIncrement|null $precision = null,

        /** Grouping strategy. */
        public Grouping $grouping = Grouping::Auto,

        /** Scale multiplier. */
        public float $scale = 1.0,

        /** Minimum integer digits (legacy "zeros" shorthand). 0 = unset. */
        public int $zeros = 0,

        /** Full integer-width specification. Takes precedence over $zeros when set. */
        public ?IntegerWidth $integerWidth = null,

        /** Rounding mode. */
        public ?RoundingMode $roundingMode = null,

        /** Decimal separator display. */
        public DecimalSeparator $decimalSeparator = DecimalSeparator::Auto,

        /** Numbering system / digit symbols. null = locale default. */
        public ?NumberingSystem $numberingSystem = null,

        /**
         * Scale by 100 and format as percent (the %x100 concise shorthand).
         * When true, $format should be Format::Percent and $scale should be 100.
         */
        public bool $percentScale = false,
    ) {
        $this->precision = $precision ?? $this->defaultPrecision();
    }

    // ------------------------------------------------------------------
    // Factory helpers
    // ------------------------------------------------------------------

    /**
     * Parse a space-separated long-form (or mixed) skeleton string.
     *
     * @param array<int, string> $tokens Space-separated tokens, with '::' already stripped.
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

    /**
     * Try to create a Skeleton from a simple Pattern (old-style single-word option).
     * Returns null when the pattern does not match a known shorthand.
     */
    public static function tryCreateFromPattern(Pattern $pattern): ?self
    {
        $option = trim($pattern->value);

        if ($option === 'currency') {
            return new self(new Currency());
        }

        // Concise percent+scale: %x100
        if ($option === '%x100') {
            return new self(
                format: Format::Percent,
                scale: 100.0,
                percentScale: true,
            );
        }

        // Format enum values (integer, percent, scientific, …)
        $format = Format::tryFrom($option);

        if ($format !== null) {
            return new self($format);
        }

        // Legacy zeros shorthand: "000" → minimum 3 integer digits
        $zeros = self::parseZeros($option);

        if ($zeros !== null) {
            return new self(zeros: $zeros);
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Serialise back to ICU skeleton string
    // ------------------------------------------------------------------

    public function __toString(): string
    {
        $tokens = [];
        $canBeSimple = true;   // can we emit without "::" prefix?

        // --- Unit / format ---
        if ($this->format instanceof Currency) {
            $tokens[] = 'currency/' . $this->format->value;
            $canBeSimple = true; // currency/XXX alone is valid without ::
        } elseif ($this->format instanceof MeasureUnit) {
            $tokens[] = 'measure-unit/' . $this->format->unit;

            if ($this->format->perUnit !== null) {
                $tokens[] = 'per-measure-unit/' . $this->format->perUnit;
            }

            $canBeSimple = false;
        } elseif ($this->format !== Format::Decimal) {
            $tokens[] = $this->format->value;
        }

        // --- Notation ---
        if ($this->notation !== Notation::Standard) {
            $tokens[] = $this->notation->value . (string) ($this->scientificOptions ?? '');
            $canBeSimple = false;
        }

        // --- Sign ---
        if ($this->sign !== Sign::Auto) {
            $tokens[] = $this->sign->value;
            $canBeSimple = false;
        }

        // --- Unit width ---
        if ($this->unitWidth !== UnitWidth::Short) {
            $tokens[] = $this->unitWidth->value;
            $canBeSimple = false;
        }

        // --- Precision ---
        $defaultPrec = $this->defaultPrecision();
        $precToken = $this->precisionToken($this->precision);
        $defaultPrecToken = $this->precisionToken($defaultPrec);

        if ($precToken !== $defaultPrecToken) {
            $tokens[] = $precToken;
            $canBeSimple = false;
        }

        // --- Grouping ---
        if ($this->grouping !== Grouping::Auto) {
            $tokens[] = $this->grouping->value;
            $canBeSimple = false;
        }

        // --- Scale ---
        if ($this->scale !== 1.0 && !$this->percentScale) {
            $tokens[] = 'scale/' . self::formatFloat($this->scale);
            $canBeSimple = false;
        }

        // --- Integer width ---
        if ($this->integerWidth !== null) {
            $tokens[] = (string) $this->integerWidth;
            $canBeSimple = false;
        } elseif ($this->zeros > 0) {
            $tokens[] = str_repeat('0', $this->zeros);
        }

        // --- Rounding mode ---
        if ($this->roundingMode !== null) {
            $tokens[] = $this->roundingMode->value;
            $canBeSimple = false;
        }

        // --- Decimal separator ---
        if ($this->decimalSeparator !== DecimalSeparator::Auto) {
            $tokens[] = $this->decimalSeparator->value;
            $canBeSimple = false;
        }

        // --- Numbering system ---
        if ($this->numberingSystem !== null) {
            $tokens[] = (string) $this->numberingSystem;
            $canBeSimple = false;
        }

        if ($tokens === []) {
            return '';
        }

        // A single "simple" token can be emitted without the "::" prefix.
        if ($canBeSimple && count($tokens) === 1) {
            if ($tokens[0] === 'currency/USD') return 'currency';
            return $tokens[0]; // e.g. currency/CAD, integer, percent
        }

        return '::' . implode(' ', $tokens);
    }

    // ------------------------------------------------------------------
    // Internal parsing
    // ------------------------------------------------------------------

    /**
     * Parse one skeleton token and accumulate its value into $args.
     *
     * @param array<string, mixed> $args
     */
    private static function applyToken(string $token, array &$args): void
    {
        // ---- Notation ----
        if ($token === 'notation-simple') {
            $args['notation'] = Notation::Standard;
            return;
        }

        // Concise compact: K / KK
        if ($token === 'K') {
            $args['notation'] = Notation::CompactShort;
            return;
        }

        if ($token === 'KK') {
            $args['notation'] = Notation::CompactLong;
            return;
        }

        // Concise scientific/engineering: E0, E00, EE+!0, E+?00 …
        if (preg_match('/\A(EE?)((?:[+!?]|[+][!?])?)(0+)\z/', $token, $m)) {
            $args['notation'] = $m[1] === 'EE' ? Notation::Engineering : Notation::Scientific;
            $sciSign = self::parseConciseSign($m[2]);
            $minExp = strlen($m[3]);
            $args['scientificOptions'] = new ScientificOptions(
                exponentSign: $sciSign,
                minExponentDigits: $minExp,
            );
            return;
        }

        // Long-form scientific/engineering with options: scientific/sign-always/*ee
        if (str_starts_with($token, 'scientific') || str_starts_with($token, 'engineering')) {
            $parts = explode('/', $token);
            $stem = array_shift($parts);
            $args['notation'] = $stem === 'engineering' ? Notation::Engineering : Notation::Scientific;
            $sciSign = null;
            $minExp = 1;

            foreach ($parts as $opt) {
                if (str_starts_with($opt, 'sign-')) {
                    $sciSign = Sign::tryFrom($opt);
                } elseif (preg_match('/\A[*+](e+)\z/', $opt, $m)) {
                    $minExp = strlen($m[1]);
                }
            }

            $args['scientificOptions'] = new ScientificOptions(
                exponentSign: $sciSign,
                minExponentDigits: $minExp,
            );
            return;
        }

        // Long-form compact
        $notation = Notation::tryFrom($token);

        if ($notation !== null) {
            $args['notation'] = $notation;
            return;
        }

        // ---- Unit / Format ----

        // Concise percent+scale: %x100
        if ($token === '%x100') {
            $args['format'] = Format::Percent;
            $args['scale'] = 100.0;
            $args['percentScale'] = true;
            return;
        }

        // Concise percent: %
        if ($token === '%') {
            $args['format'] = Format::Percent;
            return;
        }

        // currency/XXX
        if (str_starts_with($token, 'currency/')) {
            $args['format'] = new Currency(substr($token, 9));
            return;
        }

        // measure-unit/aaaa-bbbb
        if (str_starts_with($token, 'measure-unit/')) {
            $unit = substr($token, 13);
            $existing = $args['format'] ?? Format::Decimal;
            $args['format'] = new MeasureUnit(unit: $unit, perUnit: ($existing instanceof MeasureUnit ? $existing->perUnit : null));
            return;
        }

        // per-measure-unit/aaaa-bbbb
        if (str_starts_with($token, 'per-measure-unit/')) {
            $perUnit = substr($token, 17);
            $existing = $args['format'] ?? Format::Decimal;
            $unit = $existing instanceof MeasureUnit ? $existing->unit : '';
            $args['format'] = new MeasureUnit(unit: $unit, perUnit: $perUnit);
            return;
        }

        // unit/bbb (concise measure-unit)
        if (str_starts_with($token, 'unit/')) {
            $args['format'] = new MeasureUnit(unit: substr($token, 5));
            return;
        }

        // Format enum (integer, percent, permille, scientific, base-unit, "")
        $format = Format::tryFrom($token);

        if ($format !== null) {
            $args['format'] = $format;
            return;
        }

        // ---- Sign ----
        // Concise: +! +_ +? () ()! ()? ()- +-
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

        // ---- Unit width ----
        $unitWidth = UnitWidth::tryFrom($token);

        if ($unitWidth !== null) {
            $args['unitWidth'] = $unitWidth;
            return;
        }

        // ---- Precision ----

        // precision-unlimited
        if ($token === 'precision-unlimited') {
            $args['precision'] = Precision::Unlimited;
            return;
        }

        // precision-integer (may have trailing /w)
        if ($token === 'precision-integer' || $token === 'precision-integer/w') {
            $hide = str_ends_with($token, '/w');
            $args['precision'] = Precision::Integer;

            if ($hide) {
                // wrap as PrecisionFraction with trailingZeroHideIfWhole
                $args['precision'] = new PrecisionFraction(0, 0, true);
            }

            return;
        }

        // Named precision: precision-currency-standard, precision-currency-cash
        $namedPrecision = Precision::tryFrom($token);

        if ($namedPrecision !== null) {
            $args['precision'] = $namedPrecision;
            return;
        }

        // precision-currency-standard/w, precision-currency-cash/w
        if (str_ends_with($token, '/w')) {
            $base = substr($token, 0, -2);
            $namedBase = Precision::tryFrom($base);

            if ($namedBase !== null) {
                // Store as a PrecisionFraction placeholder to carry /w flag
                // (actual currency precision is locale-driven; we just record it)
                $args['precision'] = $namedBase;
                // /w flag noted separately — extend if needed
                return;
            }
        }

        // precision-increment/dddd
        if (str_starts_with($token, 'precision-increment/')) {
            $args['precision'] = new PrecisionIncrement((float) substr($token, 20));
            return;
        }

        // Fraction precision: .00, .##, .0#, .00*, .00/@@@*, .##/@##r …
        if (str_starts_with($token, '.')) {
            $args['precision'] = self::parseFractionPrecision($token);
            return;
        }

        // . alone is equivalent to precision-integer
        if ($token === '.') {
            $args['precision'] = Precision::Integer;
            return;
        }

        // Significant digits: @@@, @##, @@@*, @@# …
        if (str_starts_with($token, '@')) {
            $args['precision'] = self::parseSignificantDigits($token);
            return;
        }

        // ---- Rounding mode ----
        $roundingMode = RoundingMode::tryFrom($token);

        if ($roundingMode !== null) {
            $args['roundingMode'] = $roundingMode;
            return;
        }

        // ---- Grouping ----

        // Concise: ,_ ,? ,!
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

        // ---- Integer width ----
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

        // Concise integer width: one or more 0s
        $zeros = self::parseZeros($token);

        if ($zeros !== null) {
            $args['zeros'] = $zeros;
            return;
        }

        // ---- Decimal separator ----
        $decimal = DecimalSeparator::tryFrom($token);

        if ($decimal !== null) {
            $args['decimalSeparator'] = $decimal;
            return;
        }

        // ---- Numbering system ----
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

    /**
     * Parse a fraction-precision token like .0# or .##/@@@*
     */
    private static function parseFractionPrecision(string $token): PrecisionFraction
    {
        // Split off any options: .00/@@@* or .00/w
        $parts = explode('/', $token, 3);
        $stem = $parts[0]; // e.g. ".00" or ".##"

        // Parse stem: . + zeros + (hashes or *)
        $body = substr($stem, 1); // strip leading dot
        preg_match('/\A(0*)([#]*)(\*)?\z/', $body, $m);
        $minFraction = strlen($m[1]);
        $unlimited = isset($m[3]) && $m[3] === '*';
        $maxFraction = $unlimited ? null : ($minFraction + strlen($m[2]));

        // Parse optional significant-digit modifier and /w flag
        $minSig = null;
        $maxSig = null;
        $sigMode = null;
        $hideIfWhole = false;

        foreach (array_slice($parts, 1) as $opt) {
            if ($opt === 'w') {
                $hideIfWhole = true;
            } elseif (preg_match('/\A(@+)([#]*)(\*|[rs])?\z/', $opt, $sm)) {
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

    /**
     * Parse a significant-digit token: @@@, @##, @@#, @@@*
     * Also accepts /w suffix.
     */
    private static function parseSignificantDigits(string $token): PrecisionSignificant
    {
        $parts = explode('/', $token, 2);
        $stem = $parts[0];
        $hideIfWhole = isset($parts[1]) && $parts[1] === 'w';

        preg_match('/\A(@+)([#]*)(\*)?\z/', $stem, $m);
        $minDigits = strlen($m[1]);
        $unlimited = isset($m[3]) && $m[3] === '*';
        $maxDigits = $unlimited ? null : ($minDigits + strlen($m[2]));

        return new PrecisionSignificant(
            minDigits: $minDigits,
            maxDigits: $maxDigits,
            trailingZeroHideIfWhole: $hideIfWhole,
        );
    }

    /**
     * Parse integer-width option string (after "integer-width/").
     * Option is: [*|#*][0*]
     */
    private static function parseIntegerWidth(string $option): IntegerWidth
    {
        if (str_starts_with($option, '*')) {
            $zeros = substr($option, 1);
            return new IntegerWidth(zeroFillTo: strlen($zeros), truncateAt: null);
        }

        preg_match('/\A(#*)(0*)\z/', $option, $m);
        $hashes = strlen($m[1]);
        $zeros = strlen($m[2]);

        return new IntegerWidth(
            zeroFillTo: $zeros,
            truncateAt: $zeros + $hashes,
        );
    }

    /**
     * Parse concise sign token: +!, +_, +?, (), ()!, ()?, ()-, +-
     */
    private static function parseConciseSign(string $token): ?Sign
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

    /**
     * Match one or more 0 characters (concise minimum-integer-digits shorthand).
     */
    private static function parseZeros(string $token): ?int
    {
        return preg_match('/\A0+\z/', $token) ? strlen($token) : null;
    }

    // ------------------------------------------------------------------
    // Precision serialisation
    // ------------------------------------------------------------------

    private function precisionToken(Precision|PrecisionFraction|PrecisionSignificant|PrecisionIncrement $precision): string
    {
        if ($precision instanceof Precision) {
            return $precision->value;
        }

        if ($precision instanceof PrecisionFraction) {
            return (string) $precision;
        }

        if ($precision instanceof PrecisionSignificant) {
            return (string) $precision;
        }

        // PrecisionIncrement
        return 'precision-increment/' . self::formatFloat($precision->value);
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

        return match ($this->format) {
            Format::Scientific  => new PrecisionFraction(minFraction: 6, maxFraction: 6),
            Format::Percent,
            Format::Integer     => Precision::Integer,
            default             => new PrecisionFraction(minFraction: 0, maxFraction: 2),
        };
    }

    // ------------------------------------------------------------------
    // Utilities
    // ------------------------------------------------------------------

    private static function formatFloat(float $value): string
    {
        // Avoid trailing zeros while keeping enough decimal precision
        $str = rtrim(number_format($value, 10, '.', ''), '0');
        return rtrim($str, '.') ?: '0';
    }
}