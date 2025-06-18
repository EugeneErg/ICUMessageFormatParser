<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

use LogicException;

final readonly class Plural extends AbstractSelect
{
    /**
     * @param Types[] ...$numbers
     */
    public function __construct(
        string $value,
        public Types $other,
        public ?Types $zero = null,
        public ?Types $one = null,
        public ?Types $two = null,
        public ?Types $few = null,
        public ?Types $many = null,
        public array $numbers = [],
    ) {
        parent::__construct($value);
    }

    public static function create(string $value, array $options = []): self
    {
        $arguments = [];
        $argumentNames = ['zero', 'one', 'two', 'few', 'many', 'other'];

        foreach ($argumentNames as $argumentName) {
            if (isset($options[$argumentName])) {
                $arguments[$argumentName] = (new Types($options[$argumentName]))->replaceVariableName('#', $value);
                unset($options[$argumentName]);
            }
        }

        foreach ($options as $key => $option) {
            if (!preg_match('{=\d+}', $key)) {
                throw new LogicException('Invalid option "' . $key . '"');
            }

            $arguments['numbers'][substr($key, 1)] = (new Types($option))->replaceVariableName('#', $value);
        }

        return new self($value, ...$arguments);
    }

    public function __toString(): string
    {
        $options = [];

        foreach ($this->getOptions() as $key => $value) {
            $options[] = $key . ' {' . $value->replaceVariableName($this->value, '#') . '}';
        }

        return '{' . $this->value . ', plural, ' . implode(' ', $options) . '}';
    }

    public function replaceRecursive(array $replace): self
    {
        return new self(
            value: $this->value,
            other: $this->other->replaceRecursive($replace),
            zero: $this->zero?->replaceRecursive($replace),
            one: $this->one?->replaceRecursive($replace),
            two: $this->two?->replaceRecursive($replace),
            few: $this->few?->replaceRecursive($replace),
            many: $this->many?->replaceRecursive($replace),
            numbers: array_map(static fn (Types $types) => $types->replaceRecursive($replace), $this->numbers),
        );
    }

    protected function getOptions(): array
    {
        /** @var array<Types|null> $namedOptions */
        $namedOptions = [
            'zero' => $this->zero,
            'one' => $this->one,
            'two' => $this->two,
            'few' => $this->few,
            'many' => $this->many,
        ];
        $result = [];

        foreach ($namedOptions as $key => $value) {
            if ($value !== null) {
                $result[$key] = $value;
            }
        }

        foreach ($this->numbers as $key => $value) {
            $result['=' . $key] = $value;
        }

        $result['other'] = $this->other;

        return $result;
    }
}