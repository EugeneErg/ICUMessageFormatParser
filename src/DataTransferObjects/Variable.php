<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

final readonly class Variable implements ICUTypeInterface
{
    public function __construct(public string $value)
    {
    }

    public static function create(string $value, array $options = []): ICUTypeInterface
    {
        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value === '#' ? '#' : '{' . $this->value . '}';
    }
}