<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Contracts;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\AbstractSelect;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variant;
use Stringable;

interface ICUTypeInterface extends Stringable
{
    public function __toString(): string;

    /**
     * @param mixed[] $options
     *
     * @return static
     */
    public static function create(string $value, array $options = []): self;

    /**
     * @param array<class-string<AbstractSelect>, array<string, string|string[]|null>> $cases
     *
     * @return Variant[]
     */
    public function getAllVariants(array $cases = []): array;

    /**
     * @return string[]
     */
    public function getAllVariables(): array;
}
