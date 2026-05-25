<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Contracts;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\AbstractSelect;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variant;
use Stringable;

interface ICUTypeInterface extends Stringable
{
    public function __toString(): string;

    public static function create(string $value, array $options = []): self;

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
