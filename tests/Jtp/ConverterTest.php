<?php namespace Jtp\Tests;

use Jtp\ClassParser;
use Jtp\Converter;
use Twig_Template;

/**
 * @coversDefaultClass \Jtp\Converter
 * @backupStaticAttributes disabled
 */
class ConverterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Twig_TemplateWrapper|\PHPUnit_Framework_MockObject_MockBuilder */
    private $mockClassParser;

    /** @var \Jtp\ClassParser|\PHPUnit_Framework_MockObject_MockBuilder */
    private $mockTwigTemplate;

    public function setUp()
    {
        $this->mockClassParser = $this->getMockBuilder(ClassParser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockTwigTemplate = $this->getMockBuilder(Twig_Template::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @covers ::__construct
     */
    public function testCanInitialize()
    {
        $converter = new Converter($this->mockClassParser);

        $this->assertInstanceOf(Converter::class, $converter);
    }

    /**
     * @covers ::getRootObject
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::generateSource
     */
    public function testCanGetRootObjectWhenJsonRootElementIsAnArrayOfObjects()
    {
        $jsonString = '[{"test":1234}]';
        $className = 'T';
        $this->mockClassParser->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function ($arg1) {
                return $arg1->test === 1234;
            }))
            ->willReturn([]);

        $converter = new Converter($this->mockClassParser);
        $converter->generateSource($jsonString, $className);
    }

    /**
     * @covers ::generateSource
     * @uses \Jtp\Converter::__construct
     * @expectedException \Jtp\JtpException
     */
    public function testWillFailToBuildSourcesWithInvalidJson()
    {
        $fixture = '';
        $converter = new Converter($this->mockClassParser);
        $converter->generateSource($fixture, '');
        $this->assertInstanceOf(Converter::class, $converter);
    }

    /**
     * @covers ::generateSource
     * @uses \Jtp\Converter::__construct
     * @expectedException \Jtp\JtpException
     */
    public function testWillFailBuildWithInvalidClassName()
    {
        $jsonString = '{"test":1234}';
        $className = '';
        $converter = new Converter($this->mockClassParser);
        $converter->generateSource($jsonString, $className);

        $this->assertInstanceOf(Converter::class, $converter);
    }

    /**
     * @covers ::generateSource
     * @uses \Jtp\Converter::__construct
     * @expectedException \Jtp\JtpException
     */
    public function testWillFailToGenerateSourceWithAnInvalidNameSpace()
    {
        $jsonString = '{"test":1234}';
        $className = 'T';
        $namespace = '\"T';
        $converter = new Converter($this->mockClassParser);
        $converter->generateSource($jsonString, $className, $namespace);
    }

    /**
     * @covers ::generateSource
     * @covers ::setClassTemplate
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     */
    public function testWillPassParsedJsonToTemplate()
    {
        $jsonString = '{"prop":1234}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockClassParser->expects($this->once())
            ->method('__invoke')
            ->willReturn(['Test' => [['name' => 'prop', 'type' => 'integer', 'value' => 1234, 'classNameSpace' => 'T']]]);
        $this->mockTwigTemplate->expects($this->once())
            ->method('render')
            ->with($this->callback(function ($arg1) {
                return $arg1['className'] === 'Test'
                    && $arg1['classProperties'][0]['name'] === 'prop'
                    && $arg1['classProperties'][0]['type'] === 'integer'
                    && $arg1['classProperties'][0]['value'] === 1234
                    && $arg1['classNamespace'] === 'T';
            }))
            ->willReturn('test');

        $converter = new Converter($this->mockClassParser);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $actual = $converter->generateSource($jsonString, $className, $namespace);

        $this->assertEquals('test', $actual['classes']['Test']);
    }

    /**
     * @covers ::save
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     */
    public function testCanSaveGeneratedSource()
    {
        $jsonString = '{"prop":1234}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockClassParser->expects($this->once())
            ->method('__invoke')
            ->willReturn([
                'Test' => [[
                    'name' => 'prop',
                    'type' => 'integer',
                    'value' => 1234,
                    'classNameSpace' => 'T'
                ]]
            ]);

        $this->mockTwigTemplate->expects($this->once())
            ->method('render')
            ->willReturn('test');

        $converter = new Converter($this->mockClassParser);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $converter->generateSource($jsonString, $className, $namespace);
        $converter->save(TEST_TEMP_DIR);

        $acutal = file_get_contents(
            TEST_TEMP_DIR . DIRECTORY_SEPARATOR . 'Test.php'
        );

        $this->assertEquals('test', $acutal);
    }

    /**
     * @covers ::save
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::generateSource
     * @expectedException \Jtp\JtpException
     */
    public function testCannotSaveToNonExistingDirectory()
    {
        $jsonString = '{"prop":1234, "test2":{"prop2":1234}}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockClassParser->expects($this->once())
            ->method('__invoke')
            ->willReturn([
                'Test' => [[
                    'name' => 'prop',
                    'type' => 'integer',
                    'value' => 1234,
                    'classNameSpace' => 'T'
                ]]
            ]);

        $this->mockTwigTemplate->expects($this->once())
            ->method('render')
            ->willReturn('test');

        $converter = new Converter($this->mockClassParser);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $converter->generateSource($jsonString, $className, $namespace);
        $converter->save('TEST_TEMP_DIR');
    }

    /**
     * @covers ::save
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::setUnitTestTemplate
     */
    public function testCanSaveUnitTestInSeparateDir()
    {
        $jsonString = '{"prop":1234}';
        $className = 'Test';
        $namespace = 'T';
        $expected = 'unit test';
        $fixtureDir = TEST_TEMP_DIR . DIRECTORY_SEPARATOR  . 'tests';

        $this->mockClassParser->expects($this->once())
            ->method('__invoke')
            ->willReturn([
                'Test' => [[
                    'name' => 'prop',
                    'type' => 'integer',
                    'value' => 1234,
                    'classNameSpace' => 'T'
                ]]
            ]);

        $this->mockTwigTemplate->expects($this->exactly(2))
            ->method('render')
            ->with($this->callback(function ($arg1) {
                return $arg1['className'] !== 'Test4';
            }))
            ->willReturn($expected);

        $converter = new Converter($this->mockClassParser);
        $converter->setClassTemplate($this->mockTwigTemplate)
            ->setUnitTestTemplate($this->mockTwigTemplate)
            ->generateSource($jsonString, $className, $namespace);

        $converter->save(TEST_TEMP_DIR, $fixtureDir);

        $actual = file_get_contents(
            $fixtureDir . DIRECTORY_SEPARATOR . 'TestTest.php'
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::generateSource
     * @covers ::setUnitTestTemplate
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::setClassTemplate
     */
    public function testCanGenerateUnitTests()
    {
        $jsonString = '{"prop":1234}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockClassParser->expects($this->once())
            ->method('__invoke')
            ->willReturn([
                'Test' => [[
                    'name' => 'prop',
                    'type' => 'integer',
                    'value' => 1234,
                    'classNameSpace' => 'T'
                ]]
            ]);

        $this->mockTwigTemplate->expects($this->once())
            ->method('render')
            ->willReturn('test');

        $mockTwigTemplate2  = $this->getMockBuilder(Twig_Template::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockTwigTemplate2->expects($this->once())
            ->method('render')
            ->willReturn('test');

        $converter = new Converter($this->mockClassParser);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $converter->setUnitTestTemplate($mockTwigTemplate2);
        $actual = $converter->generateSource($jsonString, $className, $namespace);

        $this->assertEquals('test', $actual['classes']['Test']);
    }

    /**
     * @covers ::generateSource
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::setUnitTestTemplate
     */
    public function testCanTurnOffGeneratingUnitTests()
    {
        $jsonString = '{"prop":1234}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockClassParser->expects($this->once())
            ->method('__invoke')
            ->willReturn([
                'Test' => [[
                    'name' => 'prop',
                    'type' => 'integer',
                    'value' => 1234,
                    'classNameSpace' => 'T'
                ]]
            ]);

        $this->mockTwigTemplate->expects($this->once())
            ->method('render')
            ->willReturn('test');

        $converter = new Converter($this->mockClassParser);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $actual = $converter->generateSource($jsonString, $className, $namespace);

        $this->assertCount(0, $actual['tests']);
    }

    /**
     * @covers ::generateSource
     * @covers ::withPreRenderCallback
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::setClassTemplate
     */
    public function testCanSetACallbackForPreRenderModifications()
    {
        $jsonString = '{"prop":"It\'s me"}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockClassParser->expects($this->once())
            ->method('__invoke')
            ->willReturn([
                'Test' => [[
                    'name' => 'prop',
                    'type' => 'integer',
                    'value' => 1234,
                    'classNameSpace' => 'T'
                ]]
            ]);

        $converter = new Converter($this->mockClassParser);
        $converter->setClassTemplate($this->mockTwigTemplate);

        $unitTest = $this;
        $converter->withPreRenderCallback(function ($arg1, $arg2) use ($unitTest) {
            $unitTest->assertEquals('prop', $arg1['classProperties'][0]['name']);
            $unitTest->assertFalse($arg2);
            return $arg1;
        });

        $converter->generateSource($jsonString, $className, $namespace);
    }

    /**
     * @covers ::generateSource
     * @covers ::withPreRenderCallback
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::setClassTemplate
     */
    public function testCanSetACallbackForPreRenderModificationsOfUnitTestSeparately()
    {
        $jsonString = '{"prop":"It\'s me"}';
        $className = 'Test';
        $namespace = 'T';
        $unitTest = $this;
        $counter = 0;

        $this->mockClassParser->expects($this->once())
            ->method('__invoke')
            ->willReturn([
                'Test' => [[
                    'name' => 'prop',
                    'type' => 'integer',
                    'value' => 1234,
                    'classNameSpace' => 'T'
                ]]
            ]);

        $converter = new Converter($this->mockClassParser);
        $converter->setClassTemplate($this->mockTwigTemplate)
            ->setUnitTestTemplate($this->mockTwigTemplate)
            ->withPreRenderCallback(function ($arg1, $arg2) use ($unitTest, & $counter) {
                $unitTest->assertEquals('prop', $arg1['classProperties'][0]['name']);
                if ($counter === 0) {
                    $unitTest->assertFalse($arg2);
                } else {
                    $unitTest->assertTrue($arg2);
                }
                $counter = $counter + 1;

                return $arg1;
            });

        $converter->generateSource($jsonString, $className, $namespace);
    }

    /**
     * @covers ::getRootObject
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::generateSource
     */
    public function testCanBuildClassWhenJsonRootElementIsAnObject()
    {
        $jsonString = '{"prop":1234}';
        $className = 'Test';

        $this->mockClassParser->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function ($arg1) {
                return $arg1->prop === 1234;
            }))
            ->willReturn([]);

        $converter = new Converter($this->mockClassParser);
        $converter->generateSource($jsonString, $className);
    }

    /**
     * @covers ::getRootObject
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::generateSource
     * @expectedException \Jtp\JtpException
     */
    public function testCannotBuildClassWhenJsonRootElementHasNoObject()
    {
        $jsonString = '[]';
        $className = 'Test';

        $converter = new Converter($this->mockClassParser);
        $converter->generateSource($jsonString, $className);
    }
}
?>
