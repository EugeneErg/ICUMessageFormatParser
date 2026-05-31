<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

enum Grouping: string
{
    case Off = 'group-off';
    case Min2 = 'group-min2';
    case Auto = 'group-auto';
    case OnAligned = 'group-on-aligned';
    case Thousands = 'group-thousands';

    public function shortValue(): string
    {
        return match ($this) {
            self::Off => ',_',
            self::Min2 => ',?',
            self::OnAligned => ',!',
            default => $this->value,
        };
    }
}
