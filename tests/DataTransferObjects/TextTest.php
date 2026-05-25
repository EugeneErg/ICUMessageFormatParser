<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Text;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class TextTest extends TestCase
{
    #[Test]
    public function toStringWrapsInQuotes(): void
    {
        $this->assertSame("'Hello'", (string) new Text('Hello'));
    }

    #[Test]
    public function toStringEscapesSingleQuotes(): void
    {
        $this->assertSame("'it''s'", (string) new Text("it's"));
    }

    #[Test]
    public function getAllVariables(): void
    {
        $this->assertSame([], (new Text('foo'))->getAllVariables());
    }

    #[Test]
    public function merge(): void
    {
        $a = new Text('Hello ');
        $b = new Text('World');
        $merged = $a->merge($b);
        $this->assertCount(1, $merged);
        $this->assertSame('Hello World', $merged[0]->value);
    }

    #[Test]
    public function getAllVariants(): void
    {
        $t = new Text('hi');
        $v = $t->getAllVariants();
        $this->assertCount(1, $v);
    }
}
