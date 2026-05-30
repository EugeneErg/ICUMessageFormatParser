<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Contracts\ICUTypeInterface;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Contracts\ICUTypeVariableInterface;

use function count;
use function is_string;

final readonly class Time implements ICUTypeInterface, ICUTypeVariableInterface
{
    public function __construct(public string $value, public DateTimeFormat|Message|string $format = DateTimeFormat::Medium)
    {
    }

    public function __toString(): string
    {
        $option = $this->makeOption();

        return '{' . $this->value . ', time' . ($option === null ? '' : ', ' . $option) . '}';
    }

    public static function create(string $value, array $options = []): self
    {
        /** @var array<Pattern|string> $options */
        return new self($value, self::makeOptions($options));
    }

    /**
     * @param array<Pattern|string> $options
     */
    private static function makeOptions(array $options): DateTimeFormat|Message|string
    {
        if ($options === []) {
            return DateTimeFormat::Medium;
        }

        if ($options[0] === '::') {
            unset($options[0]);

            return implode(' ', $options);
        }

        $firstOption = $options[0];
        $firstValue = $firstOption instanceof Pattern ? $firstOption->value : $firstOption;

        if (count($options) === 1) {
            $format = DateTimeFormat::tryFrom(trim($firstValue));

            if ($format !== null) {
                return $format;
            }
        }

        $messageArgs = array_map(
            static fn (Pattern|string $o): Pattern => is_string($o) ? new Pattern($o) : $o,
            $options,
        );

        return new Message(...$messageArgs);
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

    private function makeOption(): string|null
    {
        if ($this->format === DateTimeFormat::Medium) {
            return null;
        }

        if ($this->format instanceof DateTimeFormat) {
            return $this->format->value;
        }

        if (is_string($this->format)) {
            return '::' . $this->format;
        }

        return (string) $this->format;
    }
}
