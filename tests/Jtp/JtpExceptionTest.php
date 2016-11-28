<?php namespace Jtp\Tests;

use Jtp\JtpException;
use PHPUnit_Framework_TestCase;

/**
 * Description of JtpExceptionTest
 *
 * @coversDefaultClass \Jtp\JtpException
 */
class JtpExceptionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testVerifyThatADocMarkdownExceptionCanBeConstructed()
    {
        $exception = new JtpException(JtpException::UNKNOWN);

        $this->assertInstanceOf(JtpException::class, $exception);
    }

    /**
     * @covers ::getErrorMap
     * @covers ::getMessageByCode
     */
    public function testVerifyThatAnErrorCodeMapsToTheCorrectMessage()
    {
        $error = new JtpException(JtpException::UNKNOWN);

        $expectedCode = JtpException::UNKNOWN;
        $expected = $error->getMessageByCode($expectedCode);
        $this->assertEquals($expected, $error->getMessage());

        $this->assertEquals($expectedCode, $error->getCode());
    }

    /**
     * @covers ::getMessageByCode
     */
    public function testGetTheDefaultErrorWhenInvalidCodeIsUsed()
    {
        $error = new JtpException(-1);
        $expectedCode = JtpException::UNKNOWN;
        $expected = $error->getMessageByCode($expectedCode);

        $this->assertEquals($expected, $error->getMessage());
        $this->assertEquals($expectedCode, $error->getCode());
    }

    /**
     * @covers ::getMessageByCode
     */
    public function testGetMessageWithPlaceholdersFilledIn()
    {
        $error = new JtpException(-1);
        $expectedCode = 2;
        $actual = $error->getMessageByCode($expectedCode, ['test1234', 'test1234']);

        $this->assertContains('test1234', $actual);
    }
}
?>
