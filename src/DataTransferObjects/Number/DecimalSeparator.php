<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

enum DecimalSeparator: string
{
    case Auto = 'decimal-auto';
    case Always = 'decimal-always';
}
