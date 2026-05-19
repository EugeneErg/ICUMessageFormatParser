<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

use Stringable;

/**
 * Number symbols / digit system skeleton token.
 *
 * Examples:
 *   latin                    → Latin-script digits
 *   numbering-system/arab    → Arabic-Indic digits
 */
final readonly class NumberingSystem implements Stringable
{
    public function __construct(
        /** "latin" or an IANA numbering-system identifier, e.g. "arab", "deva". */
        public string $name = 'latin',
    ) {
    }

    public function __toString(): string
    {
        if ($this->name === 'latin') {
            return 'latin';
        }

        return 'numbering-system/' . $this->name;
    }
}