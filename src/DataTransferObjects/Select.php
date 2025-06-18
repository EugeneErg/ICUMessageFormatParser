<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

final readonly class Select extends AbstractSelect
{
    /**
     * @param Types[] ...$options
     */
    public function __construct(string $value, public Types $other, public array $options)
    {
        parent::__construct($value);
    }

    public static function create(string $value, array $options = []): self
    {
        $other = $options['other'];
        unset($options['other']);

        return new self(
            $value,
            (new Types($other))->replaceVariableName('#', $value),
            array_map(static fn (array $option) => (new Types($option))->replaceVariableName('#', $value), $options),
        );
    }

    public function __toString(): string
    {
        $options = [];

        foreach ($this->getOptions() as $key => $value) {
            $options[] = $key . ' {' . $value . '}';
        }

        return '{' . $this->value . ', select, ' . implode(' ', $options) . '}';
    }

    public function replaceRecursive(array $replace): self
    {
        return new self(
            $this->value,
            $this->other->replaceRecursive($replace),
            array_map(static fn (Types $types) => $types->replaceRecursive($replace), $this->options),
        );
    }

    protected function getOptions(): array
    {
        $result = $this->options;
        $result['other'] = $this->other;

        return $result;
    }
}