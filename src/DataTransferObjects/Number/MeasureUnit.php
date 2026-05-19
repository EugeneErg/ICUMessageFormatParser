<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

use Stringable;

/**
 * Measurement-unit skeleton token.
 *
 * Long form:  measure-unit/length-meter
 * Concise:    unit/meter
 *
 * The full unit identifier includes type and subtype (e.g. "length-meter").
 * The concise "unit/" form accepts only the subtype (e.g. "meter") and also
 * supports per-unit via the "-per-" infix (e.g. "furlong-per-second").
 *
 * A denominator unit can also be specified separately via per-measure-unit/aaaa-bbbb.
 */
final readonly class MeasureUnit implements Stringable
{
    public function __construct(
        /** Full identifier, e.g. "length-meter". */
        public string $unit,
        /** Optional denominator unit, e.g. "duration-second". */
        public ?string $perUnit = null,
    ) {
    }

    public function __toString(): string
    {
        $result = 'measure-unit/' . $this->unit;

        if ($this->perUnit !== null) {
            $result .= ' per-measure-unit/' . $this->perUnit;
        }

        return $result;
    }
}