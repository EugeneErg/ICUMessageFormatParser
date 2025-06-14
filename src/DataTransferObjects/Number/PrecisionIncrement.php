<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

final readonly class PrecisionIncrement
{
    public function __construct(public float $value)
    {
    }
}