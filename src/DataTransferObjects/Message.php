<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

use Stringable;

final readonly class Message implements Stringable
{
    /** @var array<string|Text> */
    public array $values;

    public function __construct(Pattern|Text ...$values)
    {
        $this->values = $values;
    }

    public function __toString(): string
    {
        return implode('', $this->values);
    }
}