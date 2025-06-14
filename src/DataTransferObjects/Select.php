<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

final readonly class Select implements ICUTypeInterface
{
    /**
     * @param ICUTypeInterface[]|null $other
     * @param ICUTypeInterface[][] ...$options
     */
    public function __construct(public string $value, public array $other, public array $options)
    {
    }

    public static function create(string $value, array $options = []): ICUTypeInterface
    {
        $other = $options['other'];
        unset($options['other']);

        return new self($value, $other, $options);
    }

    public function __toString(): string
    {
        $options = [];

        foreach ($this->options as $key => $value) {
            $options[] = $key . ' {' . implode('', $value) . '}';
        }

        $options[] = 'other {' . implode('', $this->other) . '}';

        return '{' . $this->value . ', select' . ($options === [] ? '' : ', ' . implode(' ', $options)) . '}';
    }
}