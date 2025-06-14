<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects;

enum DateTimeFormat: string
{
    case Short = 'short';
    case Medium = 'medium';
    case Long = 'long';
    case Full = 'full';
}