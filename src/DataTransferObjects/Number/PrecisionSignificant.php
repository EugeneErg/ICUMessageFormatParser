<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

use InvalidArgumentException;
use Stringable;

final readonly class PrecisionSignificant implements Stringable
{
    public function __construct(
        public int $minDigits,
        public int|null $maxDigits = null,
        public bool $trailingZeroHideIfWhole = false,
    ) {
        if ($minDigits < 1) {
            throw new InvalidArgumentException(
                "PrecisionSignificant: minDigits must be >= 1, got {$minDigits}.",
            );
        }

        if ($maxDigits !== null && $maxDigits < $minDigits) {
            throw new InvalidArgumentException(
                "PrecisionSignificant: maxDigits ({$maxDigits}) must be >= minDigits ({$minDigits}).",
            );
        }
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
