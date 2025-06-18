<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\AbstractSelect;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Cases;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Types;

readonly class Variator
{
    public function typesToCases(Types $types, ?callable $makeKey = null): Cases
    {
        $variants = $types->getAllVariants();
        $cases = [];

        foreach ($variants as $key => $variant) {
            $key = $makeKey === null ? $key : $makeKey($variant, $key);
            $cases[$key] = $variant->cases;
        }

        return new Cases(
            array_column($variants, 'types'),
            new Types([$this->createFromCases($cases)]),
        );
    }

    public function casesToTypes(Cases $cases): Types
    {
        return $cases->variator->replaceRecursive($cases->variants);
    }

    private function createFromCases(array $cases): Pattern|AbstractSelect
    {
        foreach ($cases as $classes) {
            foreach ($classes as $class => $names) {
                foreach ($names as $name => $value) {
                    $allCases = [];

                    foreach ($cases as $key => $allClasses) {
                        if (isset($allClasses[$class][$name])) {
                            $allCases[$key] = $allClasses[$class][$name];
                            unset($cases[$key][$class][$name]);
                        }
                    }

                    return $this->createSelect($class, $name, $allCases, $cases);
                }
            }
        }

        $key = array_key_first($cases);

        return new Pattern((string) $key);
    }

    /**
     * @param class-string<AbstractSelect> $class
     * @param array<string|string[]> $classCases
     * @param array<class-string<AbstractSelect>, array<string, string|string[]>> $cases
     */
    private function createSelect(string $class, string $name, array $classCases, array $cases): AbstractSelect
    {
        $options = [];

        foreach ($classCases as $key => $value) {
            $option = is_string($value) ? $value : 'other';
            $options[$option][$key] = $cases[$key];
        }

        foreach ($options as $option => $subCases) {
            $options[$option] = [$this->createFromCases($subCases)];
        }

        return $class::create($name, $options);
    }
}