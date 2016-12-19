<?php

namespace Jtp\Tests;

use Jtp\TwigTools;
use Twig_SimpleFilter;

/**
 * Class TwigToolsTest
 *
 * @package \Jtp\Tests
 * @coversDefaultClass \Jtp\TwigTools
 */
class TwigToolsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testCanInitialize()
    {
        $tt = new TwigTools();

        $this->assertInstanceOf(TwigTools::class, $tt);
    }

    /**
     * @covers ::getFilters
     * @uses \Jtp\TwigTools::__construct
     */
    public function testCanGetFilters()
    {
        $tt = new TwigTools();

        $filters = $tt->getFilters();

        $this->assertInstanceOf(Twig_SimpleFilter::class, $filters['ucfirst']);
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
     * @uses \Jtp\TwigTools::__construct
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
            'isCustomType' => false
        ];
        $expected = 'Test ';
        $actual = $tt->getFuncType($fixture);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getFuncType
     * @uses \Jtp\TwigTools::__construct
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
     * @uses \Jtp\TwigTools::__construct
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
     * @uses \Jtp\TwigTools::__construct
     */
    public function testCanGetFullNamespace()
    {
        $tt = new TwigTools(true);
        $actual = $tt->getFullNameSpace('Tests', 'Foo');

        $this->assertEquals('Tests\\Foo', $actual);
    }

    /**
     * @covers ::getPropStmt
     * @uses \Jtp\TwigTools::__construct
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
     * @uses \Jtp\TwigTools::__construct
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
     * @uses \Jtp\TwigTools::__construct
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
     * @uses \Jtp\TwigTools::__construct
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
     * @uses \Jtp\TwigTools::__construct
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

        $this->assertEquals('@var array of \\Foo', $actual);
    }

    /**
     * @covers ::getFuncType
     * @uses \Jtp\TwigTools::__construct
     */
    public function testCanGetFunctionTypeForCustomClassProperty()
    {
        $tt = new TwigTools(true);
        $fixture = [
            'paramType' => 'T\\Foo',
            'arrayType' => '',
            'value' => 'null',
            'isCustomType' => true
        ];
        $expected = '\\T\\Foo ';
        $actual = $tt->getFuncType($fixture);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getYear
     * @uses \Jtp\TwigTools::__construct
     */
    public function testCanGetYear()
    {
        $tt = new TwigTools();

        $expected = date('Y');
        $actual = $tt->getYear();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::capFirst
     * @uses \Jtp\TwigTools::__construct
     */
    public function testCanCaptializeWord()
    {
        $tt = new TwigTools();

        $expected ='Test';
        $actual = $tt->capFirst('test');

        $this->assertEquals($expected, $actual);
    }
}
