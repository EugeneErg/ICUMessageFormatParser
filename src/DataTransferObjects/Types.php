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
}