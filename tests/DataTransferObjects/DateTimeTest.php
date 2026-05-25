<?php

declare(strict_types = 1);

namespace Tests\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Date;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\DateTimeFormat;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Message;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Time;
use PHPUnit\Framework\TestCase;

final class DateTimeTest extends TestCase
{
    // --- Date ---
    public function testDateDefaultMediumOmitsFormat(): void
    {
        $d = new Date('created');
        self::assertSame('{created, date}', (string) $d);
    }

    public function testDateShortFormat(): void
    {
        $d = new Date('created', DateTimeFormat::Short);
        self::assertSame('{created, date, short}', (string) $d);
    }

    public function testDateLongFormat(): void
    {
        $d = new Date('ts', DateTimeFormat::Long);
        self::assertSame('{ts, date, long}', (string) $d);
    }

    public function testDateFullFormat(): void
    {
        $d = new Date('ts', DateTimeFormat::Full);
        self::assertSame('{ts, date, full}', (string) $d);
    }

    public function testDateSkeletonString(): void
    {
        $d = new Date('ts', 'yMMMd');
        self::assertSame('{ts, date, ::yMMMd}', (string) $d);
    }

    public function testDateMessageOption(): void
    {
        $msg = new Message(new Pattern('MM/dd/yyyy'));
        $d = new Date('ts', $msg);
        self::assertSame('{ts, date, MM/dd/yyyy}', (string) $d);
    }

    public function testDateCreateDefault(): void
    {
        $d = Date::create('ts');
        self::assertSame(DateTimeFormat::Medium, $d->format);
    }

    public function testDateCreateWithFormat(): void
    {
        $d = Date::create('ts', ['short']);
        self::assertSame(DateTimeFormat::Short, $d->format);
    }

    public function testDateCreateWithSkeleton(): void
    {
        $d = Date::create('ts', ['::', 'yMMMd']);
        self::assertSame('yMMMd', $d->format);
    }

    public function testDateGetValue(): void
    {
        self::assertSame('created', (new Date('created'))->getValue());
    }

    public function testDateGetAllVariables(): void
    {
        self::assertSame(['ts'], (new Date('ts'))->getAllVariables());
    }

    public function testDateGetAllVariants(): void
    {
        $v = (new Date('ts'))->getAllVariants();
        self::assertCount(1, $v);
    }

    // --- Time ---
    public function testTimeDefaultMediumOmitsFormat(): void
    {
        $t = new Time('created');
        self::assertSame('{created, time}', (string) $t);
    }

    public function testTimeShortFormat(): void
    {
        $t = new Time('ts', DateTimeFormat::Short);
        self::assertSame('{ts, time, short}', (string) $t);
    }

    public function testTimeSkeletonString(): void
    {
        $t = new Time('ts', 'HHmmss');
        self::assertSame('{ts, time, ::HHmmss}', (string) $t);
    }

    public function testTimeCreateWithSkeleton(): void
    {
        $t = Time::create('ts', ['::', 'HHmm']);
        self::assertSame('HHmm', $t->format);
    }
    public function testTimeGetValue(): void { self::assertSame('ts', (new Time('ts'))->getValue()); }

    // --- DateTimeFormat enum ---
    public function testDateTimeFormatValues(): void
    {
        self::assertSame('short',  DateTimeFormat::Short->value);
        self::assertSame('medium', DateTimeFormat::Medium->value);
        self::assertSame('long',   DateTimeFormat::Long->value);
        self::assertSame('full',   DateTimeFormat::Full->value);
    }
}