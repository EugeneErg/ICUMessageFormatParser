<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Contracts\ICUTypeInterface;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Contracts\ICUTypeMergeInterface;

use function assert;

final readonly class Text implements ICUTypeInterface, ICUTypeMergeInterface
{
    public function __construct(public string $value)
    {
    }

    public function __toString(): string
    {
        return '\'' . str_replace('\'', '\'\'', $this->value) . '\'';
    }

    public static function create(string $value, array $options = []): ICUTypeInterface
    {
        return new self($value);
    }

    public function getAllVariants(array $cases = []): array
    {
        return [new Variant(types: new Types([$this]))];
    }

    public function getAllVariables(): array
    {
        return [];
    }

    public function merge(ICUTypeInterface $next): array
    {
        assert($next instanceof self || $next instanceof Pattern);

        return [
            new self($this->value . $next->value),
        ];
    }
}
