<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

enum Format: string
{
    case Decimal = '';
    case Integer = 'integer';
    case Percent = 'percent';
    case Permille = 'permille';
    case Scientific = 'scientific';
    case BaseUnit = 'base-unit';
}