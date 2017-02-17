<?php namespace Jtp\Tests;

use Jtp\StdClassParser;
use Jtp\Converter;
use Twig_Template;

/**
 * @coversDefaultClass \Jtp\Converter
 * @backupStaticAttributes disabled
 */
class ConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Twig_TemplateWrapper|\PHPUnit_Framework_MockObject_MockBuilder */
    private $mockClassParser;

    /** @var \Jtp\StdClassParser|\PHPUnit_Framework_MockObject_MockBuilder */
    private $mockTwigTemplate;

    /** @var string */
    private $unitDir;

    public function setUp()
    {
        $this->mockClassParser = $this->getMockBuilder(StdClassParser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockTwigTemplate = $this->getMockBuilder(Twig_Template::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->unitDir = TEST_TEMP_DIR . DIRECTORY_SEPARATOR . 'unit'
            . DIRECTORY_SEPARATOR;
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
     * @covers ::buildSource
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
            ->with(
                $this->anything(),
                $this->equalTo('Test'),
                $this->equalTo('T'))
            ->willReturn([
                'Test' => [
                    'name' => 'Test',
                    'fullName' => '\Test',
                    'classNamespace' => 'T',
                    'properties' => [
                        ['name' => 'prop', 'type' => 'integer', 'value' => 1234, 'namespace' => '\Test']
                    ]
                ]
            ]);
        $this->mockTwigTemplate->expects($this->once())
            ->method('render')
            ->with($this->callback(function ($arg1) {
                return $arg1['name'] === 'Test'
                    && $arg1['properties'][0]['name'] === 'prop'
                    && $arg1['properties'][0]['type'] === 'integer'
                    && $arg1['properties'][0]['value'] === 1234
                    && $arg1['classNamespace'] === 'T';
            }))
            ->willReturn('test');

        $converter = new Converter($this->mockClassParser);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $actual = $converter->generateSource($jsonString, $className, $namespace);

        $this->assertEquals('test', $actual['Test']['source']);
    }

    /**
     * @covers ::save
     * @covers ::saveSourceFile
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
        $fixture = $this->unitDir . 'T'
            . DIRECTORY_SEPARATOR . 'Test.php';

        if (file_exists($fixture)) {
            unlink($fixture);
        }

        $this->mockClassParser->expects($this->once())
            ->method('__invoke')
            ->willReturn([
                'Test' => [
                    'name' => 'Test',
                    'fullName' => 'T\\Test',
                    'classNamespace' => 'T',
                    'properties' => [[
                        'name' => 'prop',
                        'type' => 'integer',
                        'value' => 1234,
                    ]]
                ]
            ]);

        $this->mockTwigTemplate->expects($this->once())
            ->method('render')
            ->willReturn(__FUNCTION__);

        $converter = new Converter($this->mockClassParser);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $converter->generateSource($jsonString, $className, $namespace);
        $converter->save($this->unitDir);

        $acutal = file_get_contents($fixture);

        $this->assertEquals(__FUNCTION__, $acutal);

        unlink($fixture);
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
                'Test' => [
                    'name' => 'Test',
                    'fullName' => '\Test',
                    'classNamespace' => '',
                    'properties' => [[
                        'name' => 'prop',
                        'type' => 'integer',
                        'value' => 1234,
                        'classNameSpace' => 'T'
                    ]]
                ]
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
        $fixtureDir = $this->unitDir . 'test';
        $file1 = $this->unitDir . 'T' . DIRECTORY_SEPARATOR . 'Test.php';
        $file2 = $fixtureDir
            . DIRECTORY_SEPARATOR . 'T'
            . DIRECTORY_SEPARATOR . 'TestTest.php';

        $this->mockClassParser->expects($this->once())
            ->method('__invoke')
            ->willReturn([
                'Test' => [
                    'name' => 'Test',
                    'fullName' => 'T\\Test',
                    'classNamespace' => 'T',
                    'properties' => [[
                        'name' => 'prop',
                        'type' => 'integer',
                        'value' => 1234,
                        'classNameSpace' => 'T'
                    ]]
                ]
            ]);

        $this->mockTwigTemplate->expects($this->exactly(2))
            ->method('render')
            ->with($this->callback(function ($arg1) {
                return $arg1['name'] !== 'Test4';
            }))
            ->willReturn(__FUNCTION__);

        $converter = new Converter($this->mockClassParser);
        $converter->setClassTemplate($this->mockTwigTemplate)
            ->setUnitTestTemplate($this->mockTwigTemplate)
            ->generateSource($jsonString, $className, $namespace);

        $converter->save($this->unitDir, $fixtureDir);

        $actual1 = file_get_contents($file1);
        $actual2 = file_get_contents($file2);

        $this->assertEquals(__FUNCTION__, $actual1);
        $this->assertEquals(__FUNCTION__, $actual2);

        deleteDir($this->unitDir . DIRECTORY_SEPARATOR . 'T');
        deleteDir($this->unitDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'T');
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
                'Test' => [
                    'name' => 'Test',
                    'fullName' => '\Test',
                    'classNamespace' => '',
                    'properties' => [[
                        'name' => 'prop',
                        'type' => 'integer',
                        'value' => 1234,
                        'classNameSpace' => 'T'
                    ]]
                ]
            ]);

        $mockTwigTemplate2  = $this->getMockBuilder(Twig_Template::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockTwigTemplate2->expects($this->once())
            ->method('render')
            ->willReturn(__FUNCTION__);

        $converter = new Converter($this->mockClassParser);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $converter->setUnitTestTemplate($mockTwigTemplate2);
        $actual = $converter->generateSource($jsonString, $className, $namespace);

        $this->assertEquals(__FUNCTION__, $actual['Test']['unitSource']);
    }

    /**
     * @covers ::generateSource
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::setUnitTestTemplate
     */
    public function testWillNotGenerateUnitTestsWhenNoUnitTestTemplateIsSet()
    {
        $jsonString = '{"prop":1234}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockClassParser->expects($this->once())
            ->method('__invoke')
            ->willReturn([
                'Test' => [
                    'name' => 'Test',
                    'fullName' => 'T\\Test',
                    'classNamespace' => 'T',
                    'properties' => [[
                        'name' => 'prop',
                        'type' => 'integer',
                        'value' => 1234,
                        'classNameSpace' => 'T'
                    ]]
                ]
            ]);

        $this->mockTwigTemplate->expects($this->once())
            ->method('render')
            ->willReturn('test');

        $converter = new Converter($this->mockClassParser);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $actual = $converter->generateSource($jsonString, $className, $namespace);

        $this->assertEmpty(0, $actual['Test']['unitSource']);
    }

    /**
     * @covers ::generateSource
     * @covers ::buildSource
     * @covers ::withPreRenderCallback
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::setClassTemplate
     */
    public function testCanSetACallbackForPreRenderModifications()
    {
        $jsonString = '{"prop":1234}';
        $className = 'Test';
        $namespace = 'T';

        $this->mockClassParser->expects($this->once())
            ->method('__invoke')
            ->willReturn([
                'Test' => [
                    'name' => 'Test',
                    'fullName' => 'T\\Test',
                    'classNamespace' => 'T',
                    'properties' => [[
                        'name' => 'prop',
                        'type' => 'integer',
                        'value' => 1234,
                        'classNameSpace' => 'T'
                    ]]
                ]
            ]);

        $converter = new Converter($this->mockClassParser);
        $converter->setClassTemplate($this->mockTwigTemplate);

        $unitTest = $this;
        $converter->withPreRenderCallback(function ($arg1, $arg2) use ($unitTest) {
            $unitTest->assertEquals('Test', $arg1);
            $unitTest->assertEquals('prop', $arg2['properties'][0]['name']);
            return $arg2;
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



    /**
     * @covers ::save
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::setClassTemplate
     * @uses \Jtp\Converter::setUnitTestTemplate
     */
    public function testWillSaveASingleNestedObjectInNamspaceSubDirectory()
    {
        $jsonString = '{"baz":{"member1":1234}}';
        $className = 'Bar';
        $namespace = 'Foo';
        // Foo/
        //   XBar/
        //     Baz.php
        //   Bar.php
        $expected = 'unit test';

        $this->mockClassParser->expects($this->once())
            ->method('__invoke')
            ->willReturn([
                'Bar' => [
                    'name' => 'Bar',
                    'fullName' => 'Foo\\Bar',
                    'classNamespace' => 'Foo',
                    'properties' => [[
                        'access' => '',
                        'name' => '',
                        'type' => '',
                        'isCustomType' => '',
                        'paramType' => '',
                        'value' => '',
                        'arrayType' => '',
                        'namespace' => 'Foo'
                    ]]
                ],
                'Baz' => [
                    'name' => 'Baz',
                    'fullName' => 'Foo\\XBar\\Baz',
                    'classNamespace' => 'Foo\\XBar',
                    'properties' => [[
                        'access' => '',
                        'name' => '',
                        'type' => '',
                        'isCustomType' => '',
                        'paramType' => '',
                        'value' => '',
                        'arrayType' => '',
                        'namespace' => 'Foo\\XBar'
                    ]]
                ]
            ]);

        $this->mockTwigTemplate->expects($this->any(4))
            ->method('render')
            ->willReturn($expected);

        $converter = new Converter($this->mockClassParser);
        $converter->setClassTemplate($this->mockTwigTemplate);
        $converter->setUnitTestTemplate($this->mockTwigTemplate);
        $converter->generateSource($jsonString, $className, $namespace);

        $srcDir = $this->unitDir . 'src';
        $testDir = $this->unitDir . 'test';
        $converter->save($srcDir, $testDir);

        $srcDir = $this->unitDir . 'src' . DIRECTORY_SEPARATOR;
        $testDir = $this->unitDir . 'test' . DIRECTORY_SEPARATOR;
        $file1 = $srcDir . $namespace . DIRECTORY_SEPARATOR . 'Bar.php';
        $file2 = $srcDir . $namespace . DIRECTORY_SEPARATOR . 'XBar' . DIRECTORY_SEPARATOR . 'Baz.php';
        $file3 = $testDir . $namespace . DIRECTORY_SEPARATOR . 'BarTest.php';
        $file4 = $testDir . $namespace . DIRECTORY_SEPARATOR . 'XBar' . DIRECTORY_SEPARATOR . 'BazTest.php';

        $actual = file_exists($file1)
            && file_exists($file2)
            && file_exists($file3)
            && file_exists($file4);

        $this->assertTrue($actual);

        deleteDir($srcDir . DIRECTORY_SEPARATOR . 'Foo');
        deleteDir($testDir . DIRECTORY_SEPARATOR . 'Foo');
    }

    /**
     * @covers ::save
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::generateSource
     * @uses \Jtp\Converter::getRootObject
     * @uses \Jtp\Converter::buildSource
     * @expectedException \Jtp\JtpException
     */
    public function testCannotSaveUnitTestToNonExistingDirectory()
    {
        $converter = new Converter($this->mockClassParser);
        $converter->save($this->unitDir, 'TEST_TEMP_DIR');
    }

    /**
     * @covers ::saveMapFile
     * @uses \Jtp\Converter::__construct
     * @uses \Jtp\Converter::generateSource
     */
    public function testCanSaveAClassMap()
    {
        $jsonString = '{"prop":1234}';
        $className = 'Foo';
        $fixture = [
            'Foo' => [
                'name' => 'Foo',
                'fullName' => 'Foo',
                'classNamespace' => 'Foo',
                'properties' => [[
                    'access' => 'private',
                    'name' => 'prop',
                    'type' => 'integer',
                    'isCustomType' => false,
                    'paramType' => 'int',
                    'value' => 1234,
                    'arrayType' => '',
                    'namespace' => ''
                ]]
            ]
        ];

        $this->mockClassParser->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function ($arg1) {
                return $arg1->prop === 1234;
            }))
            ->willReturn($fixture);

        $converter = new Converter($this->mockClassParser);
        $converter->generateSource($jsonString, $className);

        $this->assertTrue($converter->saveMapFile($this->unitDir));
        unlink($this->unitDir . 'map.php');
    }
}
?>
