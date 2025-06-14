<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

use LogicException;

final readonly class SelectOrdinal implements ICUTypeInterface
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
                $options[] = $key . ' {' . implode('', self::setVarName($this->value, '#', $value)) . '}';
            }
        }

        foreach ($this->numbers as $key => $value) {
            $options[] = '=' . $key . ' {' . implode('', self::setVarName($this->value, '#', $value)) . '}';
        }

        $options[] = 'other {' . implode('', self::setVarName($this->value, '#', $this->other)) . '}';

        return '{' . $this->value . ', plural' . ($options === [] ? '' : ', ' . implode(' ', self::setVarName($this->value, '#', $options))) . '}';
    }

    private static function setVarName(string $from, string $to, ?array $option): ?array
    {
        return $option === null
            ? null
            : array_map(
                static fn (mixed $item) => $item instanceof Variable && $item->value === $from
                    ? new Variable($to)
                    : $item,
                $option,
            );
    }
}