<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\AbstractSelect;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Types;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variant;

readonly class Variator
{
    /**
     * @param Variant[] $variants
     */
    public function create(array $variants): Types
    {
        $cases = [];

        foreach ($variants as $key => $variant) {
            $cases[$key] = $variant->cases;
        }

        return new Types([$this->createFromCases($cases)]);
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