<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

final readonly class CompactShortNotation extends NumberNotation
{
    public function notation(): Notation
    {
        return Notation::CompactShort;
    }

    public function __toString(): string
    {
        return 'compact-short';
    }
}