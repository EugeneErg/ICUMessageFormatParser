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
    case ExceptZero = 'sign-except-zero';
    case AccountingExceptZero = 'sign-accounting-except-zero';
    case Negative = 'sign-negative';
    case AccountingNegative = 'sign-accounting-negative';

    public static function tryFromShortOrLong(string $value): ?self
    {
        return self::tryFrom($value) ?? self::tryFromShort($value);
    }

    private static function tryFromShort(string $value): ?self
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
            self::Always => '+!',
            self::Auto => '+?',
            self::Never => '+_',
            default => $this->value,
        };
    }
}