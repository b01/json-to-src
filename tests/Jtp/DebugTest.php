<?php namespace Jtp\Tests;

use Jtp\Debug;
use Jtp\Tests\Mocks\Debuggable;
use Jtp\Tests\Mocks\DebugIt;

/**
 * Description of DebugTest
 *
 * @author Khalifah
 * @coversDefaultClass \Jtp\Debug
 */
class DebugTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::isDebugOn
     * @covers ::setDebugMode
     */
    public function testCanEnableDebugging()
    {
        DebugIt::setDebugMode(true);

        $mock = $this->getMockBuilder(Debuggable::class)
            ->getMock();

        $mock->expects($this->once())
            ->method('console');

        $debug = new DebugIt($mock);
        $debug->test1();

        DebugIt::setDebugMode(false);
    }
}
