<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

enum Notation: string
{
    case Standard = 'standard';
    case Scientific = 'scientific';
    case Engineering = 'engineering';
    case CompactShort = 'compact-short';
    case CompactLong = 'compact-long';
}