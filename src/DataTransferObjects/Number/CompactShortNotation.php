<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

final readonly class CompactShortNotation extends NumberNotation
{
    public function __toString(): string
    {
        return 'compact-short';
    }
}