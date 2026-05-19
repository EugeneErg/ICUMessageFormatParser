<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

use Stringable;

/**
 * Additional options for scientific / engineering notation.
 *
 * scientific/sign-always/*ee
 *   → exponentSign = Sign::Always, minExponentDigits = 2
 *
 * Concise equivalents:
 *   E0     → scientific, default options
 *   E00    → scientific, minExponentDigits = 2
 *   EE+!0  → engineering, exponentSign = Always
 *   E+?00  → scientific, exponentSign = ExceptZero, minExponentDigits = 2
 */
final readonly class ScientificOptions implements Stringable
{
    public function __construct(
        /** Sign display for the exponent part only. null = default (auto). */
        public ?Sign $exponentSign = null,
        /** Minimum number of exponent digits (1 = default). */
        public int $minExponentDigits = 1,
    ) {
    }

    public function __toString(): string
    {
        $options = [];

        if ($this->exponentSign !== null && $this->exponentSign !== Sign::Auto) {
            $options[] = '/' . $this->exponentSign->value;
        }

        if ($this->minExponentDigits > 1) {
            $options[] = '/*' . str_repeat('e', $this->minExponentDigits);
        }

        return implode('', $options);
    }
}