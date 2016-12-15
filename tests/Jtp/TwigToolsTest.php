<?php

namespace Jtp\Tests;

use Jtp\TwigTools;

/**
 * Class TwigToolsTest
 *
 * @package \Jtp\Tests
 * @coversDefaultClass \Jtp\TwigTools
 */
class TwigToolsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::getFilters
     * @uses \Jtp\TwigTools::__construct
     */
    public function testCanInitialize()
    {
        $tt = new TwigTools();

        $this->assertTrue(is_array($tt->getFilters()));
    }

    /**
     * @covers ::getFunctions
     * @uses \Jtp\TwigTools::__construct
     */
    public function testCanGetFunctions()
    {
        $tt = new TwigTools();

        $this->assertTrue(is_array($tt->getFunctions()));
    }

    /**
     * @covers ::getFuncType
     * @covers ::__construct
     */
    public function testCanGetFunctionTypeForProperty()
    {
        $tt = new TwigTools(true);
        $fixture = [
            'name' => 'test',
            'type' => 'array',
            'paramType' => 'array',
            'arrayType' => 'Test',
            'value' => '[]',
            'isCustomType' => true
        ];
        $expected = 'Test ';
        $actual = $tt->getFuncType($fixture);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getFuncType
     * @covers ::__construct
     */
    public function testCanGetFunctionTypeForPropertyWithNamespace()
    {
        $tt = new TwigTools(true);
        $fixture = [
            'name' => 'test',
            'type' => 'array',
            'paramType' => 'array',
            'arrayType' => 'Company',
            'value' => '[]',
            'isCustomType' => true
        ];
        $expected = '\\Tests\\Company ';
        $actual = $tt->getFuncType($fixture, 'Tests');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getFuncType
     * @covers ::__construct
     */
    public function testCanOmitTypeHintsForScalars()
    {
        $tt = new TwigTools(false);
        $fixture = [
            'name' => 'test',
            'type' => 'integer',
            'paramType' => 'int',
            'arrayType' => '',
            'value' => '1234',
            'isCustomType' => false
        ];
        $expected = '';
        $actual = $tt->getFuncType($fixture, 'Tests');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getAssignProp
     * @covers ::__construct
     */
    public function testGetArrayAssignmentForPropertyOfTypeArray()
    {
        $tt = new TwigTools(true);
        $fixture = [
            'name' => 'test',
            'type' => 'array',
            'paramType' => 'array',
            'arrayType' => 'Company',
            'value' => '[]',
            'isCustomType' => true
        ];

        $actual = $tt->getAssignProp($fixture);
        $this->assertEquals('test[]', $actual);
    }

    /**
     * @covers ::getFullNameSpace
     * @covers ::__construct
     */
    public function testCanGetFullNamespace()
    {
        $tt = new TwigTools(true);
        $actual = $tt->getFullNameSpace('Tests', 'Foo');

        $this->assertEquals('Tests\\Foo', $actual);
    }

    /**
     * @covers ::getPropStmt
     * @covers ::__construct
     */
    public function testCanGetPropStmtArray()
    {
        $tt = new TwigTools(true);
        $fixture = [
            'name' => 'test',
            'type' => 'array',
            'paramType' => 'array',
            'arrayType' => 'Company',
            'value' => '[]',
            'isCustomType' => true
        ];
        $actual = $tt->getPropStmt($fixture);

        $this->assertEquals("\n        \$this->test = [];", $actual);
    }

    /**
     * @covers ::getPropStmt
     * @covers ::__construct
     */
    public function testCanGetPropStmtString()
    {
        $tt = new TwigTools(true);
        $fixture = [
            'name' => 'test',
            'type' => 'string',
            'value' => ''
        ];
        $actual = $tt->getPropStmt($fixture);

        $this->assertEquals("\n        \$this->test = '';", $actual);
    }

    /**
     * @covers ::getVarType
     * @covers ::__construct
     */
    public function testCanGetVarType()
    {
        $tt = new TwigTools(true);
        $fixture = [
            'name' => 'test',
            'type' => 'string',
            'paramType' => 'string',
            'arrayType' => '',
            'value' => '',
            'isCustomType' => false
        ];
        $actual = $tt->getVarType($fixture, null);

        $this->assertEquals('@var string', $actual);
    }

    /**
     * @covers ::getVarType
     * @covers ::__construct
     */
    public function testCanGetVarTypeCustom()
    {
        $tt = new TwigTools(true);
        $fixture = [
            'name' => 'test',
            'paramType' => 'Bar',
            'arrayType' => '',
            'isCustomType' => true
        ];
        $actual = $tt->getVarType($fixture, 'Tests');

        $this->assertEquals('@var \\Tests\\Bar', $actual);
    }

    /**
     * @covers ::getVarType
     * @covers ::__construct
     */
    public function testCanGetVarTypeArray()
    {
        $tt = new TwigTools(true);
        $fixture = [
            'paramType' => 'array',
            'arrayType' => 'Foo',
            'isCustomType' => true
        ];
        $actual = $tt->getVarType($fixture, 'Tests');

        $this->assertEquals('@var array of \\Tests\\Foo', $actual);
    }
}
