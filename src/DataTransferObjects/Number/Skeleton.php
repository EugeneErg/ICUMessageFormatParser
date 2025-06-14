<?php

declare(strict_types = 1);

namespace EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use LogicException;
use Stringable;

final readonly class Skeleton implements Stringable
{
    public Precision|PrecisionFractional|PrecisionIncrement $precision;

    public function __construct(
        public Format|Currency $format = Format::Decimal,
        public Notation $notation = Notation::Standard,
        public Sign $sign = Sign::Auto,
        public UnitWidth $unitWidth = UnitWidth::Short,
        Precision|PrecisionFractional|PrecisionIncrement|null $precision = null,
        public bool $group = true,
        public float $scale = 1,
        public int $zeros = 0,
    ) {
        $this->precision = $precision ?? $this->getDefaultPrecision();
    }

    private function getDefaultPrecision(): Precision|PrecisionFractional
    {
        if ($this->format instanceof Currency) {
            return Precision::CurrencyStandard;
        }

        return match ($this->format) {
            Format::Scientific => new PrecisionFractional(6),
            Format::Percent,
            Format::Integer => Precision::Integer,
            default => new PrecisionFractional(2),
        };
    }

    public static function createFromOptions(array $options): self
    {
        $skeleton = [];
        $skeletonOptions = [
            'format' => static fn (string $option) => self::tryMakeFormat($option),
            'notation' => static fn (string $option) => Notation::tryFrom($option),
            'sign' => static fn (string $option) => Sign::tryFrom($option),
            'unitWidth' => static fn (string $option) => UnitWidth::tryFrom($option),
            'precision' => static fn (string $option) => self::tryMakePrecision($option),
            'group' => static fn (string $option) => self::tryMakeGroup($option),
            'scale' => static fn (string $option) => self::tryMakeScale($option),
            'zeros' => static fn (string $option) => self::tryMakeZeros($option),
        ];

        foreach ($options as $option) {
            foreach ($skeletonOptions as $name => $function) {
                $value = $function($option);

                if ($value !== null) {
                    $skeleton[$name] = $value;
                    unset($skeletonOptions[$name]);

                    continue (2);
                }
            }

            throw new LogicException('Unexpected skeleton option.');
        }

        return new self(...$skeleton);
    }

    public static function tryCreateFromPattern(Pattern $pattern): ?self
    {
        $option = trim($pattern->value);

        if ($option === 'currency') {
            return new self(new Currency());
        }

        $format = Format::tryFrom($option);

        if ($format !== null) {
            return new self($format);
        }

        $zeros = self::tryMakeZeros($option);

        return $zeros === null
            ? null
            : new self(zeros: $zeros);
    }

    public function __toString(): string
    {
        $canBeConstant = true;
        $options = [];

        if ($this->format !== Format::Decimal) {
            if ($this->format instanceof Currency) {
                $options[] = 'currency/' . $this->format->value;
                $canBeConstant = $this->format->value === 'USD';
            } else {
                $options[] = $this->format->value;
            }
        }

        if ($this->notation !== Notation::Standard) {
            $options[] = $this->notation->value;
            $canBeConstant = false;
        }

        if ($this->sign !== Sign::Auto) {
            $options[] = $this->sign->value;
            $canBeConstant = false;
        }

        if ($this->unitWidth !== UnitWidth::Short) {
            $options[] = $this->unitWidth->value;
            $canBeConstant = false;
        }

        if ($this->precision != $this->getDefaultPrecision()) {
            if ($this->precision instanceof Precision) {
                $options[] = $this->precision->value;
            } elseif ($this->precision instanceof PrecisionFractional) {
                $options[] = 'precision-fractional/' . $this->precision->value;
            } else {
                $options[] = 'precision-increment/' . $this->precision->value;
            }

            $canBeConstant = false;
        }

        if (!$this->group) {
            $options[] = 'group-off';
            $canBeConstant = false;
        }

        if ($this->scale !== 1.) {
            $options[] = 'scale/' . $this->scale;
            $canBeConstant = false;
        }

        if ($this->zeros !== 0) {
            $options[] = str_repeat('0', $this->zeros);
        }

        if ($options === []) {
            return '';
        }

        if ($canBeConstant && count($options) === 1) {
            return $options[0] === 'currency/USD' ? 'currency' : $options[0];
        }

        return '::' . implode(' ', $options);
    }

    private static function tryMakeFormat(string $option): Format|Currency|null
    {
        $result = Format::tryFrom($option);

        if ($result !== null) {
            return $result;
        }

        $result = self::getValue('currency/', $option);

        return $result === null
            ? null
            : new Currency($result);
    }

    private static function tryMakePrecision(string $option): Precision|PrecisionFractional|PrecisionIncrement|null
    {
        $result = Precision::tryFrom($option);

        if ($result !== null) {
            return $result;
        }

        $result = self::getValue('precision-fractional/', $option);

        if ($result !== null) {
            return new PrecisionFractional((int) $result);
        }

        $result = self::getValue('precision-increment/', $option);

        return $result === null
            ? null
            : new PrecisionIncrement((float) $result);
    }

    private static function tryMakeGroup(string $option): ?bool
    {
        return ['group-on' => true, 'group-off' => false][$option] ?? null;
    }

    private static function tryMakeScale(string $option): ?float
    {
        $result = self::getValue('scale/', $option);

        return $result === null
            ? null
            : (float) $result;
    }

    private static function getValue(string $startWith, string $option): ?string
    {
        return str_starts_with($option, $startWith)
            ? substr($option, strlen($startWith))
            : null;
    }

    private static function tryMakeZeros(string $option): ?int
    {
        return preg_match('{\\A0+\\z}', $option)
            ? strlen($option)
            : null;
    }
}