<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Contracts;

interface ICUTypeMergeInterface extends ICUTypeInterface
{
    /**
     * @return ICUTypeInterface[]
     */
    public function merge(ICUTypeInterface $next): array;
}