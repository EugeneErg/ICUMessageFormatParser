<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Date;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\DateTimeFormat;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Message;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Time;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DateTimeTest extends TestCase
{
    // --- Date ---
    #[Test]
    public function dateDefaultMediumOmitsFormat(): void
    {
        $d = new Date('created');
        $this->assertSame('{created, date}', (string) $d);
    }

    #[Test]
    public function dateShortFormat(): void
    {
        $d = new Date('created', DateTimeFormat::Short);
        $this->assertSame('{created, date, short}', (string) $d);
    }

    #[Test]
    public function dateLongFormat(): void
    {
        $d = new Date('ts', DateTimeFormat::Long);
        $this->assertSame('{ts, date, long}', (string) $d);
    }

    #[Test]
    public function dateFullFormat(): void
    {
        $d = new Date('ts', DateTimeFormat::Full);
        $this->assertSame('{ts, date, full}', (string) $d);
    }

    #[Test]
    public function dateSkeletonString(): void
    {
        $d = new Date('ts', 'yMMMd');
        $this->assertSame('{ts, date, ::yMMMd}', (string) $d);
    }

    #[Test]
    public function dateMessageOption(): void
    {
        $msg = new Message(new Pattern('MM/dd/yyyy'));
        $d = new Date('ts', $msg);
        $this->assertSame('{ts, date, MM/dd/yyyy}', (string) $d);
    }

    #[Test]
    public function dateCreateDefault(): void
    {
        $d = Date::create('ts');
        $this->assertSame(DateTimeFormat::Medium, $d->format);
    }

    #[Test]
    public function dateCreateWithFormat(): void
    {
        $d = Date::create('ts', ['short']);
        $this->assertSame(DateTimeFormat::Short, $d->format);
    }

    #[Test]
    public function dateCreateWithSkeleton(): void
    {
        $d = Date::create('ts', ['::', 'yMMMd']);
        $this->assertSame('yMMMd', $d->format);
    }

    #[Test]
    public function dateGetValue(): void
    {
        $this->assertSame('created', (new Date('created'))->getValue());
    }

    #[Test]
    public function dateGetAllVariables(): void
    {
        $this->assertSame(['ts'], (new Date('ts'))->getAllVariables());
    }

    #[Test]
    public function dateGetAllVariants(): void
    {
        $v = (new Date('ts'))->getAllVariants();
        $this->assertCount(1, $v);
    }

    // --- Time ---
    #[Test]
    public function timeDefaultMediumOmitsFormat(): void
    {
        $t = new Time('created');
        $this->assertSame('{created, time}', (string) $t);
    }

    #[Test]
    public function timeShortFormat(): void
    {
        $t = new Time('ts', DateTimeFormat::Short);
        $this->assertSame('{ts, time, short}', (string) $t);
    }

    #[Test]
    public function timeSkeletonString(): void
    {
        $t = new Time('ts', 'HHmmss');
        $this->assertSame('{ts, time, ::HHmmss}', (string) $t);
    }

    #[Test]
    public function timeCreateWithSkeleton(): void
    {
        $t = Time::create('ts', ['::', 'HHmm']);
        $this->assertSame('HHmm', $t->format);
    }

    #[Test]
    public function timeGetValue(): void
    {
        $this->assertSame('ts', (new Time('ts'))->getValue());
    }

    // --- DateTimeFormat enum ---
    #[Test]
    public function dateTimeFormatValues(): void
    {
        $this->assertSame('short', DateTimeFormat::Short->value);
        $this->assertSame('medium', DateTimeFormat::Medium->value);
        $this->assertSame('long', DateTimeFormat::Long->value);
        $this->assertSame('full', DateTimeFormat::Full->value);
    }
}
