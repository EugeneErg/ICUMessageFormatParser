<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

use InvalidArgumentException;
use Stringable;

final readonly class Currency implements Stringable
{
    public function __construct(public string $value = 'USD')
    {
        if ($value === '') {
            throw new InvalidArgumentException('Currency: currency code must not be empty.');
        }
    }

    public function __toString(): string
    {
        return 'currency/' . $this->value;
    }
}
