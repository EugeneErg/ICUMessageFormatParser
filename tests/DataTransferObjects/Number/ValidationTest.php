<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\Number;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Currency;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Format;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\IntegerWidth;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\MeasureUnit;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\NumberingSystem;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Precision;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\PrecisionFraction;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\PrecisionFractional;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\PrecisionIncrement;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\PrecisionSignificant;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Skeleton;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\UnitWidth;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ValidationTest extends TestCase
{
    // --- Currency ---
    #[Test]
    public function currencyEmptyCodeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Currency('');
    }

    // --- MeasureUnit ---
    #[Test]
    public function measureUnitEmptyUnitThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MeasureUnit('');
    }

    #[Test]
    public function measureUnitEmptyPerUnitThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MeasureUnit('length-meter', '');
    }

    // --- NumberingSystem ---
    #[Test]
    public function numberingSystemEmptyNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new NumberingSystem('');
    }

    // --- IntegerWidth ---
    #[Test]
    public function integerWidthNegativeZeroFillThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new IntegerWidth(-1);
    }

    #[Test]
    public function integerWidthTruncateLessThanFillThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new IntegerWidth(5, 3);
    }

    #[Test]
    public function integerWidthTruncAndFillBothZeroIsValid(): void
    {
        $iw = new IntegerWidth(0, 0); // trunc form
        $this->assertSame('integer-width-trunc', (string) $iw);
    }

    // --- PrecisionFraction ---
    #[Test]
    public function precisionFractionNegativeMinThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionFraction(-1);
    }

    #[Test]
    public function precisionFractionMaxLessThanMinThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionFraction(3, 1);
    }

    #[Test]
    public function precisionFractionMinSigLessThan1Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionFraction(0, 2, false, 0);
    }

    #[Test]
    public function precisionFractionMaxSigLessThanMinSigThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionFraction(0, 2, false, 3, 1);
    }

    #[Test]
    public function precisionFractionInvalidModeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionFraction(0, 2, false, 2, 4, 'x');
    }

    // --- PrecisionSignificant ---
    #[Test]
    public function precisionSignificantMinLessThan1Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionSignificant(0);
    }

    #[Test]
    public function precisionSignificantMaxLessThanMinThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionSignificant(4, 2);
    }

    // --- PrecisionIncrement ---
    #[Test]
    public function precisionIncrementZeroThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionIncrement(0.0);
    }

    #[Test]
    public function precisionIncrementNegativeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionIncrement(-1.0);
    }

    // --- Skeleton ---
    #[Test]
    public function skeletonScaleZeroThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Skeleton(scale: 0.0);
    }

    #[Test]
    public function skeletonIsoCodeWithoutCurrencyThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Skeleton(format: Format::Decimal, unitWidth: UnitWidth::IsoCode);
    }

    #[Test]
    public function skeletonHiddenWithoutCurrencyThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Skeleton(format: Format::Decimal, unitWidth: UnitWidth::Hidden);
    }

    #[Test]
    public function skeletonFullNameWithDecimalThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Skeleton(format: Format::Decimal, unitWidth: UnitWidth::FullName);
    }

    #[Test]
    public function skeletonFullNameWithMeasureUnitIsValid(): void
    {
        $sk = new Skeleton(format: new MeasureUnit('length-meter'), unitWidth: UnitWidth::FullName);
        $this->assertSame(UnitWidth::FullName, $sk->unitWidth);
    }

    #[Test]
    public function skeletonCurrencyPrecisionWithDecimalThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Skeleton(format: Format::Decimal, precision: Precision::CurrencyStandard);
    }

    #[Test]
    public function skeletonCurrencyPrecisionWithCurrencyIsValid(): void
    {
        $sk = new Skeleton(format: new Currency('USD'), precision: Precision::CurrencyStandard);
        $this->assertSame(Precision::CurrencyStandard, $sk->precision);
    }

    #[Test]
    public function precisionFractionalConstructor(): void
    {
        // PrecisionFractional is a simple value object (covers constructor)
        $p = new PrecisionFractional(3);
        $this->assertSame(3, $p->value);
    }
}
