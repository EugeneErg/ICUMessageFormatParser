<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

use LogicException;

final readonly class Plural implements ICUTypeInterface
{
    /**
     * @var ICUTypeInterface[][]
     */
    public array $numbers;

    /**
     * @param string $value
     * @param ICUTypeInterface[]|null $other
     * @param ICUTypeInterface[]|null $zero
     * @param ICUTypeInterface[]|null $one
     * @param ICUTypeInterface[]|null $two
     * @param ICUTypeInterface[]|null $few
     * @param ICUTypeInterface[]|null $many
     * @param ICUTypeInterface[][] ...$numbers
     */
    public function __construct(
        public string $value,
        public array $other,
        public ?array $zero = null,
        public ?array $one = null,
        public ?array $two = null,
        public ?array $few = null,
        public ?array $many = null,
        array ...$numbers,
    ) {
        $this->numbers = $numbers;
    }

    public static function create(string $value, array $options = []): ICUTypeInterface
    {
        $zero = $options['zero'] ?? null;
        $one = $options['one'] ?? null;
        $two = $options['two'] ?? null;
        $few = $options['few'] ?? null;
        $many = $options['many'] ?? null;
        $other = $options['other'];
        unset($options['zero'], $options['one'], $options['two'], $options['few'], $options['many'], $options['other']);
        $numbers = [];

        foreach ($options as $key => $option) {
            if (!preg_match('{=\d+}', $key)) {
                throw new LogicException('Invalid option "' . $key . '"');
            }

            $numbers[substr($key, 1)] = $option;
        }

        return new self($value, $other, $zero, $one, $two, $few, $many, ...$numbers);
    }

    public function __toString(): string
    {
        $namedOptions = [
            'zero' => $this->zero,
            'one' => $this->one,
            'two' => $this->two,
            'few' => $this->few,
            'many' => $this->many,
        ];
        $options = [];

        foreach ($namedOptions as $key => $value) {
            if ($value !== null) {
                $options[] = $key . ' {' . implode('', $value) . '}';
            }
        }

        foreach ($this->numbers as $key => $value) {
            $options[] = '=' . $key . ' {' . implode('', $value) . '}';
        }

        $options[] = 'other {' . implode('', $this->other) . '}';

        return '{' . $this->value . ', plural' . ($options === [] ? '' : ', ' . implode(' ', $options)) . '}';
    }
}