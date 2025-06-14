<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

final readonly class PrecisionFractional
{
    public function __construct(public int $value)
    {
    }
}