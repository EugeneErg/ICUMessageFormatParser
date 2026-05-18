<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

use Stringable;

/**
 * Fraction-precision stem: .{zeros}{hashes}[*]
 *
 * Examples:
 *   .00    → minFraction=2, maxFraction=2
 *   .##    → minFraction=0, maxFraction=2
 *   .0#    → minFraction=1, maxFraction=2
 *   .00*   → minFraction=2, maxFraction=null (unlimited)
 *   .      → equivalent to precision-integer
 *
 * Optional trailing-zero display (/w) and significant-digit modifier (@@@*) are also stored.
 */
final readonly class PrecisionFraction implements Stringable
{
    public function __construct(
        public int $minFraction = 0,
        /** null means unlimited */
        public ?int $maxFraction = 0,
        /** /w: hide trailing zeros when the value is whole */
        public bool $trailingZeroHideIfWhole = false,
        /** significant-digit modifier: min sig digits (from leading @s) */
        public ?int $minSignificantDigits = null,
        /** significant-digit modifier: max sig digits (@ + #), null = unlimited */
        public ?int $maxSignificantDigits = null,
        /** s = strict, r = relaxed, null = withMinDigits / withMaxDigits variant */
        public ?string $significantDigitsMode = null,
    ) {
    }

    public function __toString(): string
    {
        $zeros = str_repeat('0', $this->minFraction);
        $hashes = $this->maxFraction === null
            ? '*'
            : str_repeat('#', max(0, $this->maxFraction - $this->minFraction));

        $stem = '.' . $zeros . $hashes;

        if ($this->minSignificantDigits !== null) {
            $atSigns = str_repeat('@', $this->minSignificantDigits);
            $sigHashes = $this->maxSignificantDigits === null
                ? '*'
                : str_repeat('#', max(0, $this->maxSignificantDigits - $this->minSignificantDigits));
            $stem .= '/' . $atSigns . $sigHashes;

            if ($this->significantDigitsMode !== null) {
                $stem .= $this->significantDigitsMode;
            }
        }

        if ($this->trailingZeroHideIfWhole) {
            $stem .= '/w';
        }

        return $stem;
    }
}