<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

enum UnitWidth: string
{
    case Narrow = 'unit-width-narrow';
    case Short = 'unit-width-short';
    case FullName = 'unit-width-full-name';
    case IsoCode = 'unit-width-iso-code';
    case Hidden = 'unit-width-hidden';
}