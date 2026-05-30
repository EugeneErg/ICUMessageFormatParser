<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

enum Notation: string
{
    /**
     * Programmatic API name (ICU4J/ICU4C).
     */
    case Standard = '';

    /**
     * Skeleton token form — same meaning as Standard.
     */
    case NotationSimple = 'notation-simple';

    case Scientific = 'scientific';
    case Engineering = 'engineering';
    case CompactShort = 'compact-short';
    case CompactLong = 'compact-long';

    public static function tryFromShortOrLong(string $value): self|null
    {
        return self::tryFrom($value) ?? self::tryFromShort($value);
    }

    private static function tryFromShort(string $value): self|null
    {
        foreach (self::cases() as $case) {
            if ($case->shortValue() === $value) {
                return $case;
            }
        }

        return null;
    }

    public function shortValue(): string
    {
        return match ($this) {
            self::Scientific => 'E',
            self::Engineering => 'EE',
            default => $this->value,
        };
    }
}
