<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

use InvalidArgumentException;
use Stringable;

final readonly class PrecisionIncrement implements Stringable
{
    public function __construct(public float $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException(
                "PrecisionIncrement: value must be > 0, got {$value}.",
            );
        }
    }

    public function __toString(): string
    {
        return 'precision-increment/' . $this->value;
    }
}
