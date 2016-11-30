<?php namespace Jtp\Tests;

use Jtp\Converter;
use Twig_Template;

/**
 * @coversDefaultClass \Jtp\Converter
 * @backupStaticAttributes disabled
 */
class ConverterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Twig_TemplateWrapper|\PHPUnit_Framework_MockObject_MockBuilder */
    private $mockTwigTemplate;

    public function setUp()
    {
        $this->mockTwigTemplate = $this->getMockBuilder(Twig_Template::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @covers ::__construct
     */
    public function testCanInitialize()
    {
        $jsonFile = '{"test":1234}';
        $className = 'T';
        $converter = new Converter($jsonFile, $className);

        $this->assertInstanceOf(Converter::class, $converter);
    }

    /**
     * @covers ::__construct
     * @expectedException \Jtp\JtpException
     */
    public function testWillFailInitializeWithInvalidJson()
    {
        $fixture = '';
        $converter = new Converter($fixture, '');

        $this->assertInstanceOf(Converter::class, $converter);
    }

    /**
     * @covers ::__construct
     * @expectedException \Jtp\JtpException
     */
    public function testWillFailInitializeWithInvalidClassName()
    {
        $jsonFile = '{"test":1234}';
        $className = '';
        $converter = new Converter($jsonFile, $className);

        $this->assertInstanceOf(Converter::class, $converter);
    }

    /**
     * @covers ::__construct
     * @expectedException \Jtp\JtpException
     */
    public function testWillFailInitializeWithInvalidNameSpace()
    {
        $jsonFile = '{"test":1234}';
        $className = 'T';
        $namespace = '\\T';

        $converter = new Converter($jsonFile, $className, $namespace);

        $this->assertInstanceOf(Converter::class, $converter);
    }



    /**
     * @covers ::generateSource
     * @covers ::setClassTemplate
     * @covers ::parseProperty
     * @covers ::parseClassData
     * @uses \Jtp\Converter::__construct
     */
    public function testWillGenerateSource()
    {
        $jsonFile = '{"prop":1234}';
        $className = 'Test';
        $namespace = 'T';

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

        $converter = new Converter($jsonFile, $className, $namespace);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $actual = $converter->generateSource();

        $this->assertEquals('test', $actual['classes']['Test']);
    }

    /**
     * @covers ::generateSource
     * @covers ::setClassTemplate
     * @covers ::parseProperty
     * @covers ::parseClassData
     * @uses \Jtp\Converter::__construct
     */
    public function testWillGenerateSourceIncludingNestedClasses()
    {
        $jsonFile = '{"prop":1234, "test2":{"prop2":1234}}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockTwigTemplate->expects($this->exactly(2))
            ->method('render')
            ->with($this->callback(function ($arg1) {
                if ($arg1['className'] === 'Test') {
                return $arg1['classProperties'][0]['name'] === 'prop'
                    && $arg1['classProperties'][0]['type'] === 'integer'
                    && $arg1['classProperties'][0]['value'] === 1234
                    && $arg1['classNamespace'] === 'T';
                } else if ($arg1['className'] === 'Test2') {
                return $arg1['classProperties'][0]['name'] === 'prop2'
                    && $arg1['classProperties'][0]['type'] === 'integer'
                    && $arg1['classProperties'][0]['value'] === 1234
                    && $arg1['classNamespace'] === 'T';
                }
            }))
            ->willReturn('test');

        $converter = new Converter($jsonFile, $className, $namespace);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $actual = $converter->generateSource();

        $this->assertEquals('test', $actual['classes']['Test']);
    }

    /**
     * @covers ::parseClassData
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::parseProperty
     */
    public function testWillNotGoOverRecursionLimit()
    {
        $jsonFile = '{"prop":1234, "test2":{"prop2":1234, "test3":{"prop":1234, "test4":{"prop":1234}}}}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockTwigTemplate->expects($this->exactly(3))
            ->method('render')
            ->with($this->callback(function ($arg1) {
                return $arg1['className'] !== 'Test4';
            }))
            ->willReturn('test');

        $converter = new Converter($jsonFile, $className, $namespace);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $actual = $converter->generateSource();

        $this->assertEquals('test', $actual['classes']['Test']);

        unset($converter);
    }

    /**
     * @covers ::isDebugOn
     * @covers ::setDebugMode
     * @covers ::debugParseClasses
     * @covers ::parseClassData
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     */
    public function testCanEnableDebugging()
    {
        $jsonFile = '{"prop":1234, "test2":{"prop2":1234}}';
        $className = 'Test';
        $namespace = 'T';

        $this->setOutputCallback(function ($actual) {
            $message = "recursion: 1" . PHP_EOL
                . "className: Test2" . PHP_EOL
                . "properties:" . PHP_EOL
                . "  int prop2" . PHP_EOL . PHP_EOL;

            $message .= "recursion: 0" . PHP_EOL
                . "className: Test" . PHP_EOL
                . "properties:" . PHP_EOL
                . "  int prop" . PHP_EOL
                . "  Test2 test2" . PHP_EOL . PHP_EOL;
            $this->assertEquals($message, $actual);
        });
        Converter::setDebugMode(true);
        $converter = new Converter($jsonFile, $className, $namespace, 3, true);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $converter->generateSource();
        Converter::setDebugMode(false);
    }

    /**
     * @covers ::save
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::parseClassData
     */
    public function testCanSaveGeneratedSource()
    {
        $jsonFile = '{"prop":1234}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockTwigTemplate->expects($this->once())
            ->method('render')
            ->willReturn('test');

        $converter = new Converter($jsonFile, $className, $namespace);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $converter->generateSource();
        $converter->save(TEST_TEMP_DIR);

        $acutal = file_get_contents(
            TEST_TEMP_DIR . DIRECTORY_SEPARATOR . 'Test.php'
        );

        $this->assertEquals('test', $acutal);
    }

    /**
     * @covers ::save
     * @uses \Jtp\Converter::__construct
     * @expectedException \Jtp\JtpException
     */
    public function testCannotSaveToNonExistingDirectory()
    {
        $jsonFile = '{"prop":1234, "test2":{"prop2":1234}}';
        $className = 'Test';
        $namespace = 'T';

        $converter = new Converter($jsonFile, $className, $namespace);
        $converter->save('TEST_TEMP_DIR');
    }

    /**
     * @covers ::generateSource
     * @covers ::setGenUnitTests
     * @covers ::setUnitTestTemplate
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::parseProperty
     * @uses \Jtp\Converter::parseClassData
     */
    public function testCanGenerateUnitTests()
    {
        $jsonFile = '{"prop":1234}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockTwigTemplate->expects($this->once())
            ->method('render')
            ->willReturn('test');

        $mockTwigTemplate2  = $this->getMockBuilder(Twig_Template::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockTwigTemplate2->expects($this->once())
            ->method('render')
            ->willReturn('test');

        $converter = new Converter($jsonFile, $className, $namespace);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $converter->setUnitTestTemplate($mockTwigTemplate2);
        $actual = $converter->generateSource();

        $this->assertEquals('test', $actual['classes']['Test']);
    }

    /**
     * @covers ::setGenUnitTests
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::parseProperty
     * @uses \Jtp\Converter::parseClassData
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setGenUnitTests
     * @uses \Jtp\Converter::setUnitTestTemplate
     */
    public function testCanTurnOffGenerateOfUnitTests()
    {
        $jsonFile = '{"prop":1234}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockTwigTemplate->expects($this->once())
            ->method('render')
            ->willReturn('test');

        $mockTwigTemplate2  = $this->getMockBuilder(Twig_Template::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockTwigTemplate2->expects($this->never())
            ->method('render');

        $converter = new Converter($jsonFile, $className, $namespace);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $converter->setUnitTestTemplate($mockTwigTemplate2);
        $converter->setGenUnitTests(false);
        $actual = $converter->generateSource();

        $this->assertEquals('test', $actual['classes']['Test']);
    }

    /**
     * @covers ::parseClassData
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::parseProperty
     * @uses \Jtp\Converter::parseClassData
     * @uses \Jtp\Converter::generateSource
     */
    public function testCanGenSubObjects()
    {
        $jsonFile = '{"prop":[{"o2prop": 1234}]}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockTwigTemplate->expects($this->exactly(2))
            ->method('render')
            ->willReturn('test');

        $converter = new Converter($jsonFile, $className, $namespace);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $actual = $converter->generateSource();

        $this->assertArrayHasKey('Prop', $actual['classes']);
    }
}
?>
