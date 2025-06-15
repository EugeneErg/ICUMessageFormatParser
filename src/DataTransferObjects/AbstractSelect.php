<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

abstract readonly class AbstractSelect implements ICUTypeInterface
{
    public function __construct(public string $value)
    {
    }

    /**
     * @param ICUTypeInterface[][] $options
     */
    abstract public static function create(string $value, array $options = []): self;

    public function getAllVariants(array $cases = []): array
    {
        $result = [];
        $options = $this->getOptions();
        $other = $options['other'];
        unset($options['other']);

        if ($options === []) {
            return $other->getAllVariants($cases);
        }

        foreach ($options as $name => $option) {
            $subCases = $cases;
            $subCases[static::class][$this->value] = $name;
            $result[] = $option->getAllVariants($subCases);
        }

        $subCases = $cases;
        $subCases[static::class][$this->value] = array_keys($options);
        $result[] = $other->getAllVariants($subCases);

        return array_merge(...$result);
    }

    public function getAllVariables(): array
    {
        $result = [];

        foreach ($this->getOptions() as $option) {
            $result[] = $option->getAllVariables();
        }

        return array_unique(array_merge(...$result));
    }

    /**
     * @return Types[]
     */
    abstract protected function getOptions(): array;

    protected static function setVarName(string $from, string $to, ?array $option): ?Types
    {
        return $option === null
            ? null
            : new Types(array_map(
                static fn (mixed $item) => $item instanceof Variable && $item->value === $from
                    ? new Variable($to)
                    : $item,
                $option,
            ));
    }
}