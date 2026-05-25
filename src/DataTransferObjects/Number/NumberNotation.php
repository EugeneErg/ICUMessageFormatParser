<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

use Stringable;

/**
 * Sealed hierarchy for ICU number notation.
 *
 * Usage:
 *   new StandardNotation()
 *   new ScientificNotation(new ScientificOptions(Sign::Always, 2))
 *   new EngineeringNotation()
 *
 * ScientificOptions is structurally impossible without Scientific/Engineering —
 * no runtime validation needed for that constraint.
 */
abstract readonly class NumberNotation implements Stringable
{
}
