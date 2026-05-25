<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Duration;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Ordinal;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\SpellOut;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SimpleTypesTest extends TestCase
{
    // SpellOut
    #[Test]
    public function spellOutToString(): void
    {
        $this->assertSame('{amount, spellout}', (string) new SpellOut('amount'));
    }

    #[Test]
    public function spellOutGetValue(): void
    {
        $this->assertSame('n', (new SpellOut('n'))->getValue());
    }

    #[Test]
    public function spellOutGetAllVariables(): void
    {
        $this->assertSame(['n'], (new SpellOut('n'))->getAllVariables());
    }

    #[Test]
    public function spellOutGetAllVariants(): void
    {
        $this->assertCount(1, (new SpellOut('n'))->getAllVariants());
    }

    #[Test]
    public function spellOutCreate(): void
    {
        $this->assertSame('x', SpellOut::create('x')->value);
    }

    // Duration
    #[Test]
    public function durationToString(): void
    {
        $this->assertSame('{elapsed, duration}', (string) new Duration('elapsed'));
    }

    #[Test]
    public function durationGetValue(): void
    {
        $this->assertSame('t', (new Duration('t'))->getValue());
    }

    #[Test]
    public function durationGetAllVariables(): void
    {
        $this->assertSame(['t'], (new Duration('t'))->getAllVariables());
    }

    #[Test]
    public function durationGetAllVariants(): void
    {
        $this->assertCount(1, (new Duration('t'))->getAllVariants());
    }

    #[Test]
    public function durationCreate(): void
    {
        $this->assertSame('x', Duration::create('x')->value);
    }

    // Ordinal
    #[Test]
    public function ordinalToString(): void
    {
        $this->assertSame('{rank, ordinal}', (string) new Ordinal('rank'));
    }

    #[Test]
    public function ordinalGetValue(): void
    {
        $this->assertSame('n', (new Ordinal('n'))->getValue());
    }

    #[Test]
    public function ordinalGetAllVariables(): void
    {
        $this->assertSame(['n'], (new Ordinal('n'))->getAllVariables());
    }

    #[Test]
    public function ordinalGetAllVariants(): void
    {
        $this->assertCount(1, (new Ordinal('n'))->getAllVariants());
    }

    #[Test]
    public function ordinalCreate(): void
    {
        $this->assertSame('x', Ordinal::create('x')->value);
    }
}
