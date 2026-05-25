<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

use InvalidArgumentException;
use Stringable;

final readonly class IntegerWidth implements Stringable
{
    public function __construct(
        public int $zeroFillTo = 1,
        public int|null $truncateAt = null,
    ) {
        if ($zeroFillTo < 0) {
            throw new InvalidArgumentException(
                "IntegerWidth: zeroFillTo must be >= 0, got {$zeroFillTo}.",
            );
        }

        // Special case: trunc() = (0, 0) is valid
        $isTrunc = $zeroFillTo === 0 && $truncateAt === 0;

        if (!$isTrunc && $truncateAt !== null && $truncateAt < $zeroFillTo) {
            throw new InvalidArgumentException(
                "IntegerWidth: truncateAt ({$truncateAt}) must be >= zeroFillTo ({$zeroFillTo}).",
            );
        }
    }

    public function __toString(): string
    {
        if ($this->zeroFillTo === 0 && $this->truncateAt === 0) {
            return 'integer-width-trunc';
        }

        $zeros = str_repeat('0', $this->zeroFillTo);

        if ($this->truncateAt === null) {
            return 'integer-width/*' . $zeros;
        }

        $hashes = str_repeat('#', max(0, $this->truncateAt - $this->zeroFillTo));

        return 'integer-width/' . $hashes . $zeros;
    }

    public static function fromConcise(int $minDigits): self
    {
        return new self(zeroFillTo: $minDigits, truncateAt: null);
    }

    public static function trunc(): self
    {
        return new self(zeroFillTo: 0, truncateAt: 0);
    }
}
