<?php

use Dgame\Type\Type;
use PHPUnit\Framework\TestCase;
use function Dgame\Type\typeof;

class TypeTest extends TestCase
{
    public function testTypeof()
    {
        $this->assertTrue(typeof(0.0)->isFloat());
        $this->assertTrue(typeof(0)->isInt());
        $this->assertTrue(typeof('')->isString());
        $this->assertTrue(typeof('a')->isString());
        $this->assertTrue(typeof('0')->isNumeric());
        $this->assertTrue(typeof([])->isArray());
        $this->assertTrue(typeof(new self())->isObject());
        $this->assertTrue(typeof(new self())->equals(new self()));
        $this->assertFalse(typeof(null)->isObject());
        $this->assertTrue(typeof(null)->isNull());
        $this->assertTrue(typeof(null)->equals(null));
    }

    public function testImplicit()
    {
        $this->assertTrue(typeof(0.0)->isImplicit(Type::IS_INT));
        $this->assertTrue(typeof(0.0)->isImplicit(Type::IS_FLOAT));
        $this->assertTrue(typeof(0.0)->isImplicit(Type::IS_STRING));
        $this->assertTrue(typeof(0.0)->isImplicit(Type::IS_NUMERIC));
        $this->assertTrue(typeof(0.0)->isImplicit(Type::IS_BOOL));
        $this->assertTrue(typeof('0')->isImplicit(Type::IS_INT));
        $this->assertTrue(typeof('0')->isImplicit(Type::IS_FLOAT));
        $this->assertTrue(typeof('0')->isImplicit(Type::IS_BOOL));
        $this->assertTrue(typeof('0')->isImplicit(Type::IS_NUMERIC));
        $this->assertTrue(typeof('0')->isImplicit(Type::IS_STRING));
        $this->assertTrue(typeof(false)->isImplicit(Type::IS_INT));
        $this->assertTrue(typeof(false)->isImplicit(Type::IS_FLOAT));
        $this->assertTrue(typeof(false)->isImplicit(Type::IS_BOOL));
        $this->assertTrue(typeof(false)->isImplicit(Type::IS_STRING));
    }

    public function testBuiltin()
    {
        $this->assertTrue(typeof(0)->isBuiltin());
        $this->assertTrue(typeof(0.0)->isBuiltin());
        $this->assertTrue(typeof(true)->isBuiltin());
        $this->assertTrue(typeof('a')->isBuiltin());
        $this->assertTrue(typeof('0')->isBuiltin());
        $this->assertTrue(typeof([])->isBuiltin());
        $this->assertFalse(typeof(new self())->isBuiltin());
    }

    public function testIsSame()
    {
        $this->assertTrue(typeof(0.0)->isSame(typeof(3.14)));
        $this->assertFalse(typeof(0.0)->isSame(typeof(3)));
        $this->assertTrue(typeof(0)->isSame(typeof(4)));
        $this->assertFalse(typeof(0)->isSame(typeof(3.14)));
        $this->assertTrue(typeof('a')->isSame(typeof('b')));
        $this->assertFalse(typeof('a')->isSame(typeof('0')));
        $this->assertFalse(typeof('a')->isSame(typeof(0)));
        $this->assertTrue(typeof('0')->isSame(typeof('42')));
        $this->assertFalse(typeof('0')->isSame(typeof('a')));
        $this->assertFalse(typeof('0')->isSame(typeof(0)));
    }

    public function testisImplicitSame()
    {
        $this->assertTrue(typeof(0.0)->isImplicitSame(typeof(0)));
        $this->assertTrue(typeof(0)->isImplicitSame(typeof(0.0)));
        $this->assertTrue(typeof(0)->isImplicitSame(typeof('4')));
        $this->assertTrue(typeof('a')->isImplicitSame(typeof('b')));
        $this->assertFalse(typeof('a')->isImplicitSame(typeof(42)));
        $this->assertTrue(typeof('0')->isImplicitSame(typeof(42)));
    }

    public function testDefaultValue()
    {
        $this->assertEquals(0, typeof(42)->getDefaultValue());
        $this->assertEquals(0.0, typeof(2.3)->getDefaultValue());
        $this->assertEquals('', typeof('abc')->getDefaultValue());
        $this->assertEquals(false, typeof(true)->getDefaultValue());
        $this->assertEquals([], typeof([1, 2, 3])->getDefaultValue());
        $this->assertEquals(null, typeof(null)->getDefaultValue());
        $this->assertEquals(null, typeof(new self())->getDefaultValue());
    }

    public function testExport()
    {
        $this->assertEquals('int', typeof(42)->export());
        $this->assertEquals('float', typeof(2.3)->export());
        $this->assertEquals('string', typeof('abc')->export());
        $this->assertEquals('bool', typeof(true)->export());
        $this->assertEquals('array', typeof([1, 2, 3])->export());
        $this->assertEquals('null', typeof(null)->export());
        $this->assertEquals('object', typeof(new self())->export());
    }

    public function testEmptyValue()
    {
        $this->assertTrue(Type::isEmptyValue(''));
        $this->assertFalse(Type::isEmptyValue(' '));
        $this->assertFalse(Type::isEmptyValue('abc'));
        $this->assertFalse(Type::isEmptyValue('0'));
        $this->assertFalse(Type::isEmptyValue(0));
        $this->assertFalse(Type::isEmptyValue(false));
        $this->assertTrue(Type::isEmptyValue(null));
        $this->assertTrue(Type::isEmptyValue([]));
        $this->assertFalse(Type::isEmptyValue([1, 2, 3]));
    }

    public function testValidValue()
    {
        $this->assertFalse(Type::isValidValue(''));
        $this->assertTrue(Type::isValidValue(' '));
        $this->assertTrue(Type::isValidValue('abc'));
        $this->assertTrue(Type::isValidValue('0'));
        $this->assertTrue(Type::isValidValue(0));
        $this->assertFalse(Type::isValidValue(false));
        $this->assertFalse(Type::isValidValue(null));
        $this->assertFalse(Type::isValidValue([]));
        $this->assertTrue(Type::isValidValue([1, 2, 3]));
    }
}