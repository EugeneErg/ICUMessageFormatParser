<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

enum Format: string
{
    case Integer = 'integer';
    case Percent = 'percent';
    case Scientific = 'scientific';
    case Decimal = '';
}