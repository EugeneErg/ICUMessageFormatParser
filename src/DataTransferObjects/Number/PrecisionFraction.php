<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

use InvalidArgumentException;
use Stringable;

final readonly class PrecisionFraction implements Stringable
{
    public function __construct(
        public int $minFraction = 0,
        public ?int $maxFraction = 0,
        public bool $trailingZeroHideIfWhole = false,
        public ?int $minSignificantDigits = null,
        public ?int $maxSignificantDigits = null,
        public ?string $significantDigitsMode = null,
    ) {
        if ($minFraction < 0) {
            throw new InvalidArgumentException(
                "PrecisionFraction: minFraction must be >= 0, got $minFraction."
            );
        }

        if ($maxFraction !== null && $maxFraction < $minFraction) {
            throw new InvalidArgumentException(
                "PrecisionFraction: maxFraction ($maxFraction) must be >= minFraction ($minFraction)."
            );
        }

        if ($minSignificantDigits !== null && $minSignificantDigits < 1) {
            throw new InvalidArgumentException(
                "PrecisionFraction: minSignificantDigits must be >= 1, got $minSignificantDigits."
            );
        }

        if ($minSignificantDigits !== null
            && $maxSignificantDigits !== null
            && $maxSignificantDigits < $minSignificantDigits
        ) {
            throw new InvalidArgumentException(
                "PrecisionFraction: maxSignificantDigits ($maxSignificantDigits) "
                . "must be >= minSignificantDigits ($minSignificantDigits)."
            );
        }

        if ($significantDigitsMode !== null && !in_array($significantDigitsMode, ['s', 'r'], true)) {
            throw new InvalidArgumentException(
                "PrecisionFraction: significantDigitsMode must be 's', 'r', or null, "
                . "got '$significantDigitsMode'."
            );
        }
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