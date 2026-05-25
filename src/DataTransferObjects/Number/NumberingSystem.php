<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

use InvalidArgumentException;
use Stringable;

final readonly class NumberingSystem implements Stringable
{
    public function __construct(public string $name = 'latin')
    {
        if ($name === '') {
            throw new InvalidArgumentException('NumberingSystem: name must not be empty.');
        }
    }

    public function __toString(): string
    {
        return $this->name === 'latin' ? 'latin' : 'numbering-system/' . $this->name;
    }
}
