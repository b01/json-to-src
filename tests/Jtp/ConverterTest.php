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
     * @covers ::getRootObject
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
     * @covers ::getRootObject
     */
    public function testCanInitializeWithArrayOfObject()
    {
        $jsonFile = '[{"test":1234}]';
        $className = 'T';
        $converter = new Converter($jsonFile, $className);

        $this->assertInstanceOf(Converter::class, $converter);
    }

    /**
     * @covers ::__construct
     * @covers ::getRootObject
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
     * @covers ::getRootObject
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
     * @covers ::getRootObject
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
     * @uses \Jtp\Converter::getRootObject
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
     * @uses \Jtp\Converter::getRootObject
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
     * @uses \Jtp\Converter::getRootObject
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
     * @uses \Jtp\Converter::getRootObject
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
     * @uses \Jtp\Converter::getRootObject
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
     * @uses \Jtp\Converter::getRootObject
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
     * @covers ::save
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::parseClassData
     * @uses \Jtp\Converter::setUnitTestTemplate
     */
    public function testCanSaveUnitTestInSeparateDir()
    {
        $jsonFile = '{"prop":1234}';
        $className = 'Test';
        $namespace = 'T';
        $expected = 'unit test';
        $fixtureDir = TEST_TEMP_DIR . DIRECTORY_SEPARATOR  . 'tests';

        $this->mockTwigTemplate->expects($this->exactly(2))
            ->method('render')
            ->with($this->callback(function ($arg1) {
                return $arg1['className'] !== 'Test4';
            }))
            ->willReturn($expected);

        $converter = new Converter($jsonFile, $className, $namespace);
        $converter->setClassTemplate($this->mockTwigTemplate)
            ->setUnitTestTemplate($this->mockTwigTemplate)
            ->generateSource();
        $converter->save(TEST_TEMP_DIR, $fixtureDir);
        $actual = file_get_contents(
            $fixtureDir . DIRECTORY_SEPARATOR . 'TestTest.php'
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::generateSource
     * @covers ::setGenUnitTests
     * @covers ::setUnitTestTemplate
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
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
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::parseProperty
     * @uses \Jtp\Converter::parseClassData
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
     * @covers ::parseProperty
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
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

    /**
     * @covers ::parseProperty
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::parseClassData
     */
    public function testCanGenPropertiesWithArrayAsDefault()
    {
        $jsonFile = '{"prop":[]}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockTwigTemplate->expects($this->exactly(1))
            ->method('render')
            ->with($this->callback(function ($arg1) {
                if ($arg1['className'] === 'Test') {
                    return $arg1['classProperties'][0]['name'] === 'prop'
                    && $arg1['classProperties'][0]['type'] === 'array'
                    && $arg1['classProperties'][0]['paramType'] === 'array'
                    && $arg1['classProperties'][0]['value'] === '[]'
                    && $arg1['classNamespace'] === 'T';
                }
            }))
            ->willReturn('test');

        $converter = new Converter($jsonFile, $className, $namespace);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $converter->generateSource();

    }

    /**
     * @covers ::parseProperty
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::parseClassData
     */
    public function testCanGenPropertiesWithEmptyStringAsDefault()
    {
        $jsonFile = '{"prop":"It\'s me"}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockTwigTemplate->expects($this->exactly(1))
            ->method('render')
            ->with($this->callback(function ($arg1) {
                if ($arg1['className'] === 'Test') {
                    return $arg1['classProperties'][0]['name'] === 'prop'
                    && $arg1['classProperties'][0]['type'] === 'string'
                    && $arg1['classProperties'][0]['paramType'] === 'string'
                    && $arg1['classProperties'][0]['value'] === 'It\\\'s me'
                    && $arg1['classNamespace'] === 'T';
                }
            }))
            ->willReturn('test');

        $converter = new Converter($jsonFile, $className, $namespace);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $converter->generateSource();
    }

    /**
     * @covers ::withAccessLevel
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::parseProperty
     * @uses \Jtp\Converter::parseClassData
     */
    public function testCanSetAccessLevelForGenProperties()
    {
        $jsonFile = '{"prop":"It\'s me"}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockTwigTemplate->expects($this->exactly(1))
            ->method('render')
            ->with($this->callback(function ($arg1) {
                return $arg1['classProperties'][0]['access'] === 'protected';
            }))
            ->willReturn('test');

        $converter = new Converter($jsonFile, $className, $namespace);
        $converter->setClassTemplate($this->mockTwigTemplate)
            ->withAccessLevel('protected');
        $converter->generateSource();
    }

    /**
     * @covers ::withAccessLevel
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::parseProperty
     * @uses \Jtp\Converter::parseClassData
     * @expectedException \Jtp\JtpException
     *
     */
    public function testCannotSetAccessLevelWhenNotInAllowed()
    {
        $jsonFile = '{"prop":"It\'s me"}';
        $className = 'Test';
        $namespace = 'T';

        $converter = new Converter($jsonFile, $className, $namespace);
        $converter->setClassTemplate($this->mockTwigTemplate)
            ->withAccessLevel('test');
        $converter->generateSource();
    }

    /**
     * @covers ::withAllowedAccessLevels
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::parseProperty
     * @uses \Jtp\Converter::parseClassData
     * @uses \Jtp\Converter::withAccessLevel
     */
    public function testCanSetWhatAccessLevelsAreAllowed()
    {
        $jsonFile = '{"prop":"It\'s me"}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockTwigTemplate->expects($this->exactly(1))
            ->method('render')
            ->with($this->callback(function ($arg1) {
                return $arg1['classProperties'][0]['access'] === 'test';
            }))
            ->willReturn('test');

        $converter = new Converter($jsonFile, $className, $namespace);
        $converter->setClassTemplate($this->mockTwigTemplate)
            ->withAllowedAccessLevels(['test'])
            ->withAccessLevel('test');
        $converter->generateSource();
    }

    /**
     * @covers ::generateSource
     * @covers ::withPreRenderCallback
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::parseProperty
     * @uses \Jtp\Converter::parseClassData
     * @uses \Jtp\Converter::withAccessLevel
     */
    public function testCanSetACallbackForPreRenderModifications()
    {
        $jsonFile = '{"prop":"It\'s me"}';
        $className = 'Test';
        $namespace = 'T';

        $converter = new Converter($jsonFile, $className, $namespace);

        $unitTest = $this;
        $converter->setClassTemplate($this->mockTwigTemplate)
            ->withPreRenderCallback(function ($arg1, $arg2) use ($unitTest) {
                $unitTest->assertEquals('prop', $arg1['classProperties'][0]['name']);
                $unitTest->assertFalse($arg2);
                return $arg1;
            });

        $converter->generateSource();
    }

    /**
     * @covers ::generateSource
     * @covers ::withPreRenderCallback
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::parseProperty
     * @uses \Jtp\Converter::parseClassData
     * @uses \Jtp\Converter::withAccessLevel
     */
    public function testCanSetACallbackForPreRenderModificationsOfUnitTestSeparately()
    {
        $jsonFile = '{"prop":"It\'s me"}';
        $className = 'Test';
        $namespace = 'T';
        $unitTest = $this;
        $counter = 0;

        $converter = new Converter($jsonFile, $className, $namespace);

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

        $converter->generateSource();
    }

    /**
     * @covers ::getIncrementalClassName
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::parseProperty
     * @uses \Jtp\Converter::parseClassData
     * @uses \Jtp\Converter::withAccessLevel
     * @uses \Jtp\Converter::generateSource
     */
    public function testCanAppendNumberToClassNameToPreventCollision()
    {
        $jsonFile = '{"location":{"foo":1234, "location":{"bar":1234}}}';
        $className = 'Test';
        $namespace = 'T';

        $converter = new Converter($jsonFile, $className, $namespace);

        $this->mockTwigTemplate->expects($this->exactly(3))
            ->method('render')
            ->will($this->returnCallback(function ($arg1) {
                $this->assertRegExp('/(Test|Location|Location_1)/', $arg1['className']);
            }));

        $converter->setClassTemplate($this->mockTwigTemplate);

        $converter->generateSource();
    }
}
?>
