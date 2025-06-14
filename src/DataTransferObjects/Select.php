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

        return new self(
            $value,
            self::setVarName('#', $value, $other),
            array_map(static fn (array $option) => self::setVarName('#', $value, $option), $options),
        );
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