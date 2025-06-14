<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

enum Precision: string
{
    case Integer = 'precision-integer';
    case CurrencyStandard = 'precision-currency-standard';
    case CurrencyCash = 'precision-currency-cash';
}