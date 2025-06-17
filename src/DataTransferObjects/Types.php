<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

use Stringable;

final readonly class Types implements Stringable
{
    /**
     * @param ICUTypeInterface[] $types
     */
    public function __construct(public array $types = [])
    {
    }

    public function __toString(): string
    {
        return implode('', $this->types);
    }

    /**
     * @param array<class-string<AbstractSelect>, array<string, string|string[]>> $cases
     *
     * @return Variant[]
     */
    public function getAllVariants(array $cases = []): array
    {
        $typeVariants = [];

        foreach ($this->types as $type) {
            $typeVariants[] = $type->getAllVariants($cases);
        }

        $result = [new Variant(cases: $cases)];

        foreach ($typeVariants as $variants) {
            $temp = [];

            foreach ($result as $product) {
                foreach ($variants as $variant) {
                    $value = $product->merge($variant);

                    if ($value !== null) {
                        $temp[] = $value;
                    }
                }
            }

            $result = $temp;
        }

        return $result;
    }

    /**
     * @return string[]
     */
    public function getAllVariables(): array
    {
        $result = [];

        foreach ($this->types as $type) {
            $result[] = $type->getAllVariables();
        }

        return array_unique(array_merge(...$result));
    }

    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->types));
    }

    public function filter(callable $callback): self
    {
        return new self(array_filter($this->types, $callback));
    }

    public function quote(): self
    {
        return $this->map(static fn (ICUTypeInterface $type) => $type instanceof Pattern ? $type : new Text((string) $type));
    }

    public function replaceVariableName(string $from, string $to): self
    {
        return $this->map(
            static fn (ICUTypeInterface $type) => $type instanceof Variable && $type->value === $from
                ? new Variable($to)
                : $type,
        );
    }

    public function setValues(array $values): self
    {
        return $this->map(
            static fn (ICUTypeInterface $type) => $type instanceof ICUTypeVariableInterface && isset($values[$type->getValue()])
                ? new Text($values[$type->getValue()])
                : $type,
        );
    }

    public function getVariables(): self
    {
        return $this->filter(static fn (ICUTypeInterface $type) => $type instanceof ICUTypeVariableInterface);
    }
}