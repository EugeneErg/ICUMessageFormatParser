<?php
declare(strict_types = 1);
namespace Tests\DataTransferObjects;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Duration;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Ordinal;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\SpellOut;
use PHPUnit\Framework\TestCase;

final class SimpleTypesTest extends TestCase
{
    // SpellOut
    public function testSpellOutToString(): void { self::assertSame('{amount, spellout}', (string) new SpellOut('amount')); }
    public function testSpellOutGetValue(): void { self::assertSame('n', (new SpellOut('n'))->getValue()); }
    public function testSpellOutGetAllVariables(): void { self::assertSame(['n'], (new SpellOut('n'))->getAllVariables()); }
    public function testSpellOutGetAllVariants(): void { self::assertCount(1, (new SpellOut('n'))->getAllVariants()); }
    public function testSpellOutCreate(): void { self::assertSame('x', SpellOut::create('x')->value); }

    // Duration
    public function testDurationToString(): void { self::assertSame('{elapsed, duration}', (string) new Duration('elapsed')); }
    public function testDurationGetValue(): void { self::assertSame('t', (new Duration('t'))->getValue()); }
    public function testDurationGetAllVariables(): void { self::assertSame(['t'], (new Duration('t'))->getAllVariables()); }
    public function testDurationGetAllVariants(): void { self::assertCount(1, (new Duration('t'))->getAllVariants()); }
    public function testDurationCreate(): void { self::assertSame('x', Duration::create('x')->value); }

    // Ordinal
    public function testOrdinalToString(): void { self::assertSame('{rank, ordinal}', (string) new Ordinal('rank')); }
    public function testOrdinalGetValue(): void { self::assertSame('n', (new Ordinal('n'))->getValue()); }
    public function testOrdinalGetAllVariables(): void { self::assertSame(['n'], (new Ordinal('n'))->getAllVariables()); }
    public function testOrdinalGetAllVariants(): void { self::assertCount(1, (new Ordinal('n'))->getAllVariants()); }
    public function testOrdinalCreate(): void { self::assertSame('x', Ordinal::create('x')->value); }
}
