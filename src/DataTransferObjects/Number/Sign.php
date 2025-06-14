<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

enum Sign: string
{
    case Auto = 'sign-auto';
    case Always = 'sign-always';
    case Never = 'sign-never';
    case Accounting = 'sign-accounting';
    case AccountingAlways = 'sign-accounting-always';
    case AccountingExceptZero = 'sign-accounting-except-zero';
}