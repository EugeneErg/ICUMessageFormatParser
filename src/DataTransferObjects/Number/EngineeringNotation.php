<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

/**
 * Engineering notation — exponent is always a multiple of 3 (e.g. 12.3E3).
 *
 * ScientificOptions is only meaningful here — it cannot be accidentally
 * attached to compact or standard notation.
 */
final readonly class EngineeringNotation extends NumberNotation
{
    public function __construct(
        public ?ScientificOptions $options = null,
    ) {
    }

    public function notation(): Notation
    {
        return Notation::Engineering;
    }

    public function __toString(): string
    {
        return 'engineering' . (string) ($this->options ?? '');
    }
}