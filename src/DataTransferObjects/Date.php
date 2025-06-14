<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

final readonly class Date implements ICUTypeInterface
{
    public function __construct(public string $value, public DateTimeFormat|Message|string $format = DateTimeFormat::Medium)
    {
    }

    public static function create(string $value, array $options = []): self
    {
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

        if (count($options) === 1 && is_string($options[0])) {
            $format = DateTimeFormat::tryFrom(trim($options[0]));

            if ($format !== null) {
                return $format;
            }
        }

        return new Message(...$options);
    }

    public function __toString(): string
    {
        $option = $this->makeOption();

        return '{' . $this->value . ', date' . ($option === null ? '' : ', ' . $option) . '}';
    }

    private function makeOption(): ?string
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