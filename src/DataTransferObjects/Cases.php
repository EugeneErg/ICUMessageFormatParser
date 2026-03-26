<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

final readonly class Cases
{
    /**
     * @param Types[] $types
     */
    public function __construct(
        public array $types,
        public Types $variator,
    ) {
    }
}