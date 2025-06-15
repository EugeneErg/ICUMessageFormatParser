<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

use LogicException;

final readonly class SelectOrdinal extends AbstractSelect
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
        $zero = self::setVarName('#', $value, $options['zero'] ?? null);
        $one = self::setVarName('#', $value, $options['one'] ?? null);
        $two = self::setVarName('#', $value, $options['two'] ?? null);
        $few = self::setVarName('#', $value, $options['few'] ?? null);
        $many = self::setVarName('#', $value, $options['many'] ?? null);
        $other = self::setVarName('#', $value, $options['other']);
        unset($options['zero'], $options['one'], $options['two'], $options['few'], $options['many'], $options['other']);
        $numbers = [];

        foreach ($options as $key => $option) {
            if (!preg_match('{=\d+}', $key)) {
                throw new LogicException('Invalid option "' . $key . '"');
            }

            $numbers[substr($key, 1)] = self::setVarName('#', $value, $option);
        }

        return new self($value, $other, $zero, $one, $two, $few, $many, $numbers);
    }

    public function __toString(): string
    {
        $options = [];

        foreach ($this->getOptions() as $key => $value) {
            $options[] = $key . ' {' . self::setVarName($this->value, '#', $value->types) . '}';
        }

        return '{' . $this->value . ', selectordinal, ' . implode(' ', $options) . '}';
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