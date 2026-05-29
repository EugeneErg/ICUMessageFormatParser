<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Contracts\ICUTypeMergeInterface;

use function count;
use function in_array;
use function is_string;

final readonly class Variant
{
    public Types $types;

    /**
     * @param array<string, array<string, string|string[]|null>> $cases
     */
    public function __construct(
        Types|null $types = null,
        public array $cases = [],
    ) {
        $this->types = $types ?? new Types();
    }

    public function merge(self $variant): self|null
    {
        $cases = $this->mergeCases($variant->cases);

        if ($cases === null) {
            return null;
        }

        $left = $this->types->types;
        $right = $variant->types->types;

        if (count($left) === 0 || count($right) === 0) {
            return new self(
                types: new Types(array_merge($left, $right)),
                cases: $cases,
            );
        }

        $last = array_pop($left);
        $first = array_shift($right);
        $merge = $last instanceof ICUTypeMergeInterface && $first instanceof $last
            ? $last->merge($first)
            : [$last, $first];

        return new self(
            types: new Types(array_merge($left, $merge, $right)),
            cases: $cases,
        );
    }

    /**
     * @param array<string, array<string, string|string[]|null>> $cases
     *
     * @return array<string, array<string, string|string[]|null>>|null
     */
    private function mergeCases(array $cases): array|null
    {
        foreach ($this->cases as $class => $values) {
            if (!isset($cases[$class])) {
                $cases[$class] = $values;

                continue;
            }

            foreach ($values as $name => $value) {
                if (!isset($cases[$class][$name])) {
                    $cases[$class][$name] = $value;

                    continue;
                }

                $caseAIsString = is_string($value);
                $caseBIsString = is_string($cases[$class][$name]);

                if ($caseAIsString && $caseBIsString) {
                    if ($value !== $cases[$class][$name]) {
                        return null;
                    }
                } elseif ($caseAIsString) {
                    /** @var string[] $caseBValue */
                    $caseBValue = $cases[$class][$name];

                    if (in_array($value, $caseBValue, true)) {
                        return null;
                    }

                    $cases[$class][$name] = $value;
                } elseif ($caseBIsString) {
                    /** @var string $caseBString */
                    $caseBString = $cases[$class][$name];

                    /** @var string[] $valueArr */
                    $valueArr = $value;

                    if (in_array($caseBString, $valueArr, true)) {
                        return null;
                    }
                } else {
                    /** @var string[] $caseBArr */
                    $caseBArr = $cases[$class][$name];

                    /** @var string[] $value */
                    $cases[$class][$name] = array_unique(array_merge($caseBArr, $value));
                }
            }
        }

        return $cases;
    }
}
