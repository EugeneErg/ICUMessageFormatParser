<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

/**
 * Default notation — corresponds to "standard" (ICU4J API name).
 * Emits no skeleton token.
 */
final readonly class StandardNotation extends NumberNotation
{
    public function __toString(): string
    {
        return '';
    }
}