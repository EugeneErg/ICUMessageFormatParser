<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

/**
 * Scientific notation (e.g. 1.23E4).
 *
 * ScientificOptions is only meaningful here — it cannot be accidentally
 * attached to compact or standard notation.
 */
final readonly class ScientificNotation extends NumberNotation
{
    public function __construct(
        public ?ScientificOptions $options = null,
    ) {
    }

    public function __toString(): string
    {
        return 'scientific' . ($this->options ?? '');
    }
}