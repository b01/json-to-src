<?php
/**
 * @copyright ©2016 Quicken Loans Inc. All rights reserved. Trade
 * Secret, Confidential and Proprietary. Any dissemination outside
 * of Quicken Loans is strictly prohibited.
 */

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
            'subType' => 'Test',
            'value' => '[]'
        ];
        $expected = 'Test ';
        $actual = $tt->getFuncType($fixture);

        $this->assertEquals($expected, $actual);
    }
}
