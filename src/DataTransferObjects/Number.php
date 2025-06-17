<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Skeleton;

final readonly class Number implements ICUTypeInterface, ICUTypeVariableInterface
{
    public function __construct(public string $value, public Skeleton|Message $options)
    {
    }

    public static function create(string $value, array $options = []): self
    {
        return new self($value, self::makeOptions($options));
    }

    /**
     * @param array<Pattern|Text|string> $options
     */
    private static function makeOptions(array $options): Skeleton|Message
    {
        if ($options === []) {
            return new Skeleton();
        }

        if ($options[0] === '::') {
            unset($options[0]);

            return Skeleton::createFromOptions($options);
        }

        if (count($options) === 1 && $options[0] instanceof Pattern) {
            $skeleton = Skeleton::tryCreateFromPattern($options[0]);

            if ($skeleton !== null) {
                return $skeleton;
            }
        }

        return new Message(...$options);
    }

    public function __toString(): string
    {
        $options = (string) $this->options;

        return '{' . $this->value . ', number' . ($options === '' ? '' : ', ' . $options) . '}';
    }

    public function getAllVariants(array $cases = []): array
    {
        return [new Variant(types: new Types([$this]))];
    }

    public function getAllVariables(): array
    {
        return [$this->value];
    }

    public function getValue(): string
    {
        return $this->value;
    }
}