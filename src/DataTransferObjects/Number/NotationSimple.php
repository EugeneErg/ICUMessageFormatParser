<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

/**
 * Default notation — corresponds to "notation-simple" (skeleton token form).
 * Semantically identical to StandardNotation but preserves the explicit token
 * so round-trip serialisation emits "notation-simple".
 */
final readonly class NotationSimple extends NumberNotation
{
    public function __toString(): string
    {
        return 'notation-simple';
    }
}