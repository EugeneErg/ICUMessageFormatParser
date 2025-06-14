<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

final readonly class Currency
{
    public function __construct(public string $value = 'USD')
    {
    }
}