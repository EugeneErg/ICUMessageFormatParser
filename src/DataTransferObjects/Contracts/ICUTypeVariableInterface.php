<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Contracts;

interface ICUTypeVariableInterface extends ICUTypeInterface
{
    public function getValue(): string;
}