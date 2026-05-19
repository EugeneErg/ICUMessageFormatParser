<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

use Stringable;

/**
 * Integer-width skeleton token.
 *
 * Long-form examples:
 *   integer-width/*000  → zeroFillTo=3, truncateAt=null  (at least 3 integer digits)
 *   integer-width/##0   → zeroFillTo=1, truncateAt=3     (between 1 and 3)
 *   integer-width/00    → zeroFillTo=2, truncateAt=2     (exactly 2)
 *   integer-width/*     → zeroFillTo=0, truncateAt=null  (zero or more)
 *   integer-width-trunc → zeroFillTo=0, truncateAt=0
 *
 * Concise form: one or more 0 characters (minimum integer digits only).
 */
final readonly class IntegerWidth implements Stringable
{
    public function __construct(
        public int $zeroFillTo = 1,
        /** null means no upper limit */
        public ?int $truncateAt = null,
    ) {
    }

    /** Build from concise "000" form (minimum integer digits only). */
    public static function fromConcise(int $minDigits): self
    {
        return new self(zeroFillTo: $minDigits, truncateAt: null);
    }

    /** The special integer-width-trunc case. */
    public static function trunc(): self
    {
        return new self(zeroFillTo: 0, truncateAt: 0);
    }

    public function __toString(): string
    {
        if ($this->zeroFillTo === 0 && $this->truncateAt === 0) {
            return 'integer-width-trunc';
        }

        $zeros = str_repeat('0', $this->zeroFillTo);

        if ($this->truncateAt === null) {
            // unlimited upper bound → use * prefix
            return 'integer-width/*' . $zeros;
        }

        // limited upper bound → # symbols for the extra slots
        $hashes = str_repeat('#', max(0, $this->truncateAt - $this->zeroFillTo));

        return 'integer-width/' . $hashes . $zeros;
    }
}