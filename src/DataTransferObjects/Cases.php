<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

final readonly class Cases
{
    /**
     * @param Types[] $variants
     */
    public function __construct(
        public array $variants,
        public Types $variator,
    ) {
    }
}