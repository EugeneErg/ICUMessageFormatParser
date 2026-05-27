<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Text;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class PatternTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $this->assertSame('Hello', (string) new Pattern('Hello'));
    }

    #[Test]
    public function toStringEscapesQuotes(): void
    {
        $this->assertSame("it''s", (string) new Pattern("it's"));
    }

    #[Test]
    public function create(): void
    {
        $this->assertSame('test', (new Pattern('test'))->value);
    }

    #[Test]
    public function getAllVariants(): void
    {
        $p = new Pattern('hi');
        $v = $p->getAllVariants();
        $this->assertCount(1, $v);
        $this->assertSame('hi', (string) $v[0]->types);
    }

    #[Test]
    public function getAllVariables(): void
    {
        $this->assertSame([], (new Pattern('text'))->getAllVariables());
    }

    #[Test]
    public function mergeWithPattern(): void
    {
        $a = new Pattern('Hello ');
        $b = new Pattern('World');
        $merged = $a->merge($b);
        $this->assertCount(1, $merged);

        /** @var Pattern[] $merged */
        $this->assertSame('Hello World', $merged[0]->value);
    }

    #[Test]
    public function mergeWithText(): void
    {
        // merge accepts any ICUTypeInterface but Text is different class
        $a = new Pattern('Hello');
        $b = new Text(' World');

        /** @var Pattern[] $merged */
        $merged = $a->merge($b);
        $this->assertSame('Hello World', $merged[0]->value);
    }
}
