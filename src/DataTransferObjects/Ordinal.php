<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

final readonly class Ordinal implements ICUTypeInterface, ICUTypeVariableInterface
{
    public function __construct(public string $value)
    {
    }

    public static function create(string $value, array $options = []): self
    {
        return new self($value);
    }

    public function __toString(): string
    {
        return '{' . $this->value . ', ordinal}';
    }

    public function getAllVariants(array $cases = []): array
    {
        return [new Variant(types: new Types([$this]))];
    }

    public function getAllVariables(): array
    {
        return [$this->value];
    }

    public function getValue(): string
    {
        return $this->value;
    }
}