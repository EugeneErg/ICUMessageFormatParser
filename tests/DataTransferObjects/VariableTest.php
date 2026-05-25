<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects;

use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class VariableTest extends TestCase
{
    #[Test]
    public function toStringNamedVariable(): void
    {
        $this->assertSame('{name}', (string) new Variable('name'));
    }

    #[Test]
    public function toStringHashIsPlural(): void
    {
        $this->assertSame('#', (string) new Variable('#'));
    }

    #[Test]
    public function getAllVariables(): void
    {
        $this->assertSame(['count'], (new Variable('count'))->getAllVariables());
    }

    #[Test]
    public function getValue(): void
    {
        $this->assertSame('name', (new Variable('name'))->getValue());
    }

    #[Test]
    public function getAllVariants(): void
    {
        $v = (new Variable('x'))->getAllVariants();
        $this->assertCount(1, $v);
    }

    #[Test]
    public function create(): void
    {
        $v = Variable::create('test');
        $this->assertSame('test', $v->value);
    }
}
