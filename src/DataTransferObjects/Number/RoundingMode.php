<?php

declare(strict_types=1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

enum RoundingMode: string
{
    case Ceiling = 'rounding-mode-ceiling';
    case Floor = 'rounding-mode-floor';
    case Down = 'rounding-mode-down';
    case Up = 'rounding-mode-up';
    case HalfEven = 'rounding-mode-half-even';
    case HalfDown = 'rounding-mode-half-down';
    case HalfUp = 'rounding-mode-half-up';
    case Unnecessary = 'rounding-mode-unnecessary';
}
