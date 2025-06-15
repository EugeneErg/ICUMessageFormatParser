<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

use Stringable;

interface ICUTypeInterface extends Stringable
{
    public static function create(string $value, array $options = []): self;

    public function __toString(): string;

    /**
     * @param array<class-string<AbstractSelect>, array<string, string|string[]>> $cases
     *
     * @return Variant[]
     */
    public function getAllVariants(array $cases = []): array;

    /**
     * @return string[]
     */
    public function getAllVariables(): array;
}