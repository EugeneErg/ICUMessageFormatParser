<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

final readonly class Variant
{
    public Types $types;

    /**
     * @param array<class-string<AbstractSelect>, array<string, string|string[]>> $cases
     */
    public function __construct(
        ?Types $types = null,
        public array $cases = [],
    ) {
        $this->types = $types ?? new Types();
    }

    public function merge(self $variant): ?self
    {
        $cases = $this->mergeCases($variant->cases);

        return $cases === null
            ? null
            : new self(
                types: new Types(array_merge($this->types->types, $variant->types->types)),
                cases: $cases,
            );
    }

    private function mergeCases(array $cases): ?array
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
                    if (in_array($value, $cases[$class][$name])) {
                        return null;
                    }

                    $cases[$class][$name] = $value;
                } elseif ($caseBIsString) {
                    if (in_array($cases[$class][$name], $value)) {
                        return null;
                    }
                } else {
                    $cases[$class][$name] = array_unique(array_merge($cases[$class][$name], $value));
                }
            }
        }

        return $cases;
    }
}
