<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Message;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Text;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class MessageTest extends TestCase
{
    #[Test]
    public function toStringWithPattern(): void
    {
        $m = new Message(new Pattern('#,##0.00'));
        $this->assertSame('#,##0.00', (string) $m);
    }

    #[Test]
    public function toStringWithText(): void
    {
        $m = new Message(new Text('hello'));
        $this->assertSame("'hello'", (string) $m);
    }

    /**
     * Text nodes in ICU are wrapped in single quotes when serialised.
     * Pattern('prefix') + Text(' middle') + Pattern(' suffix')
     * → "prefix' middle' suffix".
     */
    #[Test]
    public function toStringMixed(): void
    {
        $m = new Message(new Pattern('prefix'), new Text(' middle'), new Pattern(' suffix'));
        $this->assertSame("prefix' middle' suffix", (string) $m);
    }

    #[Test]
    public function valuesAreStored(): void
    {
        $p = new Pattern('abc');
        $m = new Message($p);
        $this->assertCount(1, $m->values);
        $this->assertSame($p, $m->values[0]);
    }

    #[Test]
    public function emptyMessage(): void
    {
        $m = new Message();
        $this->assertSame('', (string) $m);
    }

    #[Test]
    public function textQuotesAreEscaped(): void
    {
        $m = new Message(new Text("it's"));
        $this->assertSame("'it''s'", (string) $m);
    }
}
