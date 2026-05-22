<?php
declare(strict_types = 1);
namespace Tests\DataTransferObjects;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variable;
use PHPUnit\Framework\TestCase;

final class VariableTest extends TestCase
{
    public function testToStringNamedVariable(): void { self::assertSame('{name}', (string) new Variable('name')); }
    public function testToStringHashIsPlural(): void { self::assertSame('#', (string) new Variable('#')); }
    public function testGetAllVariables(): void { self::assertSame(['count'], (new Variable('count'))->getAllVariables()); }
    public function testGetValue(): void { self::assertSame('name', (new Variable('name'))->getValue()); }
    public function testGetAllVariants(): void {
        $v = (new Variable('x'))->getAllVariants();
        self::assertCount(1, $v);
    }
    public function testCreate(): void {
        $v = Variable::create('test');
        self::assertSame('test', $v->value);
    }
}
