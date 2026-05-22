<?php
declare(strict_types = 1);

namespace Tests\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Message;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Text;
use PHPUnit\Framework\TestCase;

final class MessageTest extends TestCase
{
    public function testToStringWithPattern(): void
    {
        $m = new Message(new Pattern('#,##0.00'));
        self::assertSame('#,##0.00', (string) $m);
    }

    public function testToStringWithText(): void
    {
        $m = new Message(new Text('hello'));
        self::assertSame("'hello'", (string) $m);
    }

    /**
     * Text nodes in ICU are wrapped in single quotes when serialised.
     * Pattern('prefix') + Text(' middle') + Pattern(' suffix')
     * → "prefix' middle' suffix"
     */
    public function testToStringMixed(): void
    {
        $m = new Message(new Pattern('prefix'), new Text(' middle'), new Pattern(' suffix'));
        self::assertSame("prefix' middle' suffix", (string) $m);
    }

    public function testValuesAreStored(): void
    {
        $p = new Pattern('abc');
        $m = new Message($p);
        self::assertCount(1, $m->values);
        self::assertSame($p, $m->values[0]);
    }

    public function testEmptyMessage(): void
    {
        $m = new Message();
        self::assertSame('', (string) $m);
    }

    public function testTextQuotesAreEscaped(): void
    {
        $m = new Message(new Text("it's"));
        self::assertSame("'it''s'", (string) $m);
    }
}
