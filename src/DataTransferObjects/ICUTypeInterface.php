<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

use Stringable;

interface ICUTypeInterface extends Stringable
{
    public static function create(string $value, array $options = []): self;

    public function __toString(): string;
}