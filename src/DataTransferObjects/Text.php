<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

final readonly class Text implements ICUTypeInterface
{
    public function __construct(
        public string $value,
    ) {
    }

    public static function create(string $value, array $options = []): ICUTypeInterface
    {
        return new self($value);
    }

    public function __toString(): string
    {
        return '\'' . str_replace('\'', '\'\'', $this->value) . '\'';
    }

    public function getAllVariants(array $cases = []): array
    {
        return [new Variant(types: new Types([$this]))];
    }

    public function getAllVariables(): array
    {
        return [];
    }
}