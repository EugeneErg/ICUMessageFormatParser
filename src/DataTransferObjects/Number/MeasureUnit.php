<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

use InvalidArgumentException;
use Stringable;

final readonly class MeasureUnit implements Stringable
{
    public function __construct(
        public string $unit,
        public ?string $perUnit = null,
    ) {
        if ($unit === '') {
            throw new InvalidArgumentException('MeasureUnit: unit must not be empty.');
        }

        if ($perUnit === '') {
            throw new InvalidArgumentException('MeasureUnit: perUnit must not be empty when provided.');
        }
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