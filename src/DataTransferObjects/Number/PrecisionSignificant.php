<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

use Stringable;

/**
 * Significant-digit precision stem: {@+}{#*|*}
 *
 * Examples:
 *   @@@    → min=3, max=3  (fixedSignificantDigits)
 *   @@@*   → min=3, max=null (minSignificantDigits)
 *   @##    → min=1, max=3  (maxSignificantDigits)
 *   @@#    → min=2, max=3  (minMaxSignificantDigits)
 */
final readonly class PrecisionSignificant implements Stringable
{
    public function __construct(
        public int $minDigits,
        /** null means unlimited */
        public ?int $maxDigits = null,
        public bool $trailingZeroHideIfWhole = false,
    ) {
    }

    public function __toString(): string
    {
        $atSigns = str_repeat('@', $this->minDigits);
        $hashes = $this->maxDigits === null
            ? '*'
            : str_repeat('#', max(0, $this->maxDigits - $this->minDigits));

        $stem = $atSigns . $hashes;

        if ($this->trailingZeroHideIfWhole) {
            $stem .= '/w';
        }

        return $stem;
    }
}