<?php
declare(strict_types = 1);
namespace Tests\DataTransferObjects\Number;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Currency;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Format;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\IntegerWidth;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\MeasureUnit;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\NumberingSystem;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Precision;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\PrecisionFraction;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\PrecisionIncrement;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\PrecisionSignificant;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Skeleton;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\UnitWidth;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    // --- Currency ---
    public function testCurrencyEmptyCodeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Currency('');
    }

    // --- MeasureUnit ---
    public function testMeasureUnitEmptyUnitThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MeasureUnit('');
    }
    public function testMeasureUnitEmptyPerUnitThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MeasureUnit('length-meter', '');
    }

    // --- NumberingSystem ---
    public function testNumberingSystemEmptyNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new NumberingSystem('');
    }

    // --- IntegerWidth ---
    public function testIntegerWidthNegativeZeroFillThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new IntegerWidth(-1);
    }
    public function testIntegerWidthTruncateLessThanFillThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new IntegerWidth(5, 3);
    }
    public function testIntegerWidthTruncAndFillBothZeroIsValid(): void
    {
        $iw = new IntegerWidth(0, 0); // trunc form
        self::assertSame('integer-width-trunc', (string) $iw);
    }

    // --- PrecisionFraction ---
    public function testPrecisionFractionNegativeMinThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionFraction(-1);
    }
    public function testPrecisionFractionMaxLessThanMinThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionFraction(3, 1);
    }
    public function testPrecisionFractionMinSigLessThan1Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionFraction(0, 2, false, 0);
    }
    public function testPrecisionFractionMaxSigLessThanMinSigThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionFraction(0, 2, false, 3, 1);
    }
    public function testPrecisionFractionInvalidModeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionFraction(0, 2, false, 2, 4, 'x');
    }

    // --- PrecisionSignificant ---
    public function testPrecisionSignificantMinLessThan1Throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionSignificant(0);
    }
    public function testPrecisionSignificantMaxLessThanMinThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionSignificant(4, 2);
    }

    // --- PrecisionIncrement ---
    public function testPrecisionIncrementZeroThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionIncrement(0.0);
    }
    public function testPrecisionIncrementNegativeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PrecisionIncrement(-1.0);
    }

    // --- Skeleton ---
    public function testSkeletonScaleZeroThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Skeleton(scale: 0.0);
    }
    public function testSkeletonIsoCodeWithoutCurrencyThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Skeleton(format: Format::Decimal, unitWidth: UnitWidth::IsoCode);
    }
    public function testSkeletonHiddenWithoutCurrencyThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Skeleton(format: Format::Decimal, unitWidth: UnitWidth::Hidden);
    }
    public function testSkeletonFullNameWithDecimalThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Skeleton(format: Format::Decimal, unitWidth: UnitWidth::FullName);
    }
    public function testSkeletonFullNameWithMeasureUnitIsValid(): void
    {
        $sk = new Skeleton(format: new MeasureUnit('length-meter'), unitWidth: UnitWidth::FullName);
        self::assertSame(UnitWidth::FullName, $sk->unitWidth);
    }
    public function testSkeletonCurrencyPrecisionWithDecimalThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Skeleton(format: Format::Decimal, precision: Precision::CurrencyStandard);
    }
    public function testSkeletonCurrencyPrecisionWithCurrencyIsValid(): void
    {
        $sk = new Skeleton(format: new Currency('USD'), precision: Precision::CurrencyStandard);
        self::assertSame(Precision::CurrencyStandard, $sk->precision);
    }
}
