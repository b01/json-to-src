<?php namespace Jtp\Tests;

use Jtp\ClassParser;

/**
 * @coversDefaultClass \Jtp\ClassParser
 */
class ClassParserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::__construct
     */
    public function testCanInitialize()
    {
        $classParser = new ClassParser();

        $this->assertInstanceOf(ClassParser::class, $classParser);
    }

    /**
     * @covers ::__invoke
     * @uses \Jtp\ClassParser::__construct
     * @uses \Jtp\ClassParser::parseData
     * @uses \Jtp\ClassParser::parseProperty
     */
    public function testCanBeInvoked()
    {
        $stdObject = json_decode('{"foo":null}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new ClassParser();
        $sources = $classParser($stdObject, $className, $namespace);

        $this->assertArrayHasKey('Test', $sources);
    }

    /**
     * @covers ::parseData
     * @covers ::parseProperty
     * @uses \Jtp\ClassParser::__construct
     * @uses \Jtp\ClassParser::__invoke
     */
    public function testCanGenSubObjects()
    {
        $stdClass = json_decode('{"prop":[{"o2prop": 1234}]}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new ClassParser();
        $sources = $classParser($stdClass, $className, $namespace);

        $this->assertArrayHasKey('Prop', $sources);
    }

    /**
     * @covers ::parseProperty
     * @uses \Jtp\ClassParser::__construct
     * @uses \Jtp\ClassParser::__invoke
     * @uses \Jtp\ClassParser::parseData
     */
    public function testWillDefaultArrayPropertiesToEnptyArray()
    {
        $stdClass = json_decode('{"prop":[]}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new ClassParser();
        $sources = $classParser($stdClass, $className, $namespace);
        $actual = $sources['Test']['properties'][0];

        $this->assertEquals('array', $actual['type']);
        $this->assertEquals('[]', $actual['value']);
    }

    /**
     * @covers ::parseProperty
     * @uses \Jtp\ClassParser::__construct
     * @uses \Jtp\ClassParser::__invoke
     * @uses \Jtp\ClassParser::parseData
     */
    public function testWillDefaultStringPropertiesToAnEmptyString()
    {
        $stdClass = json_decode('{"prop":"It\'s me"}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new ClassParser();
        $sources = $classParser($stdClass, $className, $namespace);
        $actual = $sources['Test']['properties'][0];

        $this->assertEquals('string', $actual['type']);
        $this->assertEquals('It\\\'s me', $actual['value']);
    }

    /**
     * @covers ::withAccessLevel
     * @uses \Jtp\ClassParser::__construct
     * @uses \Jtp\ClassParser::parseProperty
     * @uses \Jtp\ClassParser::parseData
     */
    public function testCanChangeAccessLevelToSomethingOtherThanTheDefaultForProperties()
    {
        $stdClass = json_decode('{"prop":"It\'s me"}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new ClassParser();

        $classParser->withAccessLevel('public');

        $sources = $classParser($stdClass, $className, $namespace);
        $actual = $sources['Test']['properties'][0]['access'];

        $this->assertEquals('public', $actual);
    }

    /**
     * @covers ::withAccessLevel
     * @uses \Jtp\ClassParser::__construct
     * @uses \Jtp\ClassParser::parseProperty
     * @uses \Jtp\ClassParser::parseData
     * @expectedException \Jtp\JtpException
     *
     */
    public function testCannotSetAccessLevelToInvalidValue()
    {
        $stdClass = json_decode('{"prop":"It\'s me"}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new ClassParser();

        $classParser->withAccessLevel('level');
    }

    /**
     * @covers ::withAllowedAccessLevels
     * @uses \Jtp\ClassParser::__construct
     * @uses \Jtp\ClassParser::__invoke
     * @uses \Jtp\ClassParser::parseProperty
     * @uses \Jtp\ClassParser::parseData
     * @uses \Jtp\ClassParser::withAccessLevel
     */
    public function testCanSetWhatAccessLevelsAreAllowed()
    {
        $stdClass = json_decode('{"prop":"It\'s me"}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new ClassParser();

        $classParser->withAllowedAccessLevels(['test'])
            ->withAccessLevel('test');

        $sources = $classParser($stdClass, $className, $namespace);
        $actual = $sources['Test']['properties'][0]['access'];

        $this->assertEquals('test', $actual);
    }

    /**
     * @covers ::debugParseClasses
     * @covers ::parseData
     * @uses \Jtp\ClassParser::__construct
     * @uses \Jtp\ClassParser::__invoke
     * @uses \Jtp\ClassParser::parseProperty
     * @uses \Jtp\Debug::isDebugOn
     * @uses \Jtp\Debug::setDebugMode
     */
    public function testCanEnableDebugging()
    {
        // Must be turned off after function completes.
        ClassParser::setDebugMode(true);

        $stdClass = json_decode('{"prop":1234}');
        $className = 'Test';
        $classParser = new ClassParser();

        $this->setOutputCallback(function ($actual) {
            $message = "recursion: 0" . PHP_EOL
                . "fullName: \Test" . PHP_EOL
                . "properties:" . PHP_EOL
                . "  int prop" . PHP_EOL . PHP_EOL;
            $this->assertEquals($message, $actual);
        });

        $classParser($stdClass, $className);

        ClassParser::setDebugMode(false);
    }

    /**
     * @covers ::getIncrementalClassName
     * @uses \Jtp\ClassParser::__construct
     * @uses \Jtp\ClassParser::__invoke
     * @uses \Jtp\ClassParser::parseData
     * @uses \Jtp\ClassParser::parseProperty
     */
    public function testCanExtractAClassFromAnArrayOfObjectsWithinANestedObject()
    {
        $stdObject = json_decode('{"foo":{"foo":[{"baz":1234}]}}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new ClassParser();
        $sources = $classParser($stdObject, $className, $namespace);

        $this->assertArrayHasKey('Foo_1', $sources);
    }

    /**
     * @covers ::getIncrementalClassName
     * @uses \Jtp\ClassParser::__construct
     * @uses \Jtp\ClassParser::__invoke
     * @uses \Jtp\ClassParser::parseData
     * @uses \Jtp\ClassParser::parseProperty
     */
    public function testCanAppendNumberToClassNameToPreventCollision()
    {
        $stdClass = json_decode('{"location":{"foo":1234, "location":{"bar":1234}}}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new ClassParser();
        $sources = $classParser($stdClass, $className, $namespace);

        $this->assertArrayHasKey('Location_1', $sources);
    }

    /**
     * @covers ::parseProperty
     * @uses \Jtp\ClassParser::__construct
     * @uses \Jtp\ClassParser::__invoke
     * @uses \Jtp\ClassParser::parseData
     */
    public function testCanParseAClassFromPropertyThatIsAnObject()
    {
        $stdObject = json_decode('{"prop":1234, "test2":{"prop2":1234}}');
        $className = 'Test';
        $classParser = new ClassParser();
        $actual = $classParser($stdObject, $className);

        $this->assertArrayHasKey('Test2', $actual);
    }

    /**
     * @covers ::parseData
     * @uses \Jtp\ClassParser::__construct
     * @uses \Jtp\ClassParser::__invoke
     * @uses \Jtp\ClassParser::parseProperty
     */
    public function testWillNotGoOverRecursionLimit()
    {
        $stdClass = json_decode('{"prop":1234, "test2":{"prop2":1234, "test3":{"prop":1234, "test4":{"prop":1234}}}}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new ClassParser();
        $actual = $classParser($stdClass, $className, $namespace);

        $this->assertArrayNotHasKey('Test4', $actual);
    }

    /**
     * @covers ::parseProperty
     * @uses \Jtp\ClassParser::__construct
     * @uses \Jtp\ClassParser::__invoke
     * @uses \Jtp\ClassParser::parseData
     */
    public function testWillUppercaseArrayType()
    {
        $stdClass = json_decode('{"foo":[{"bar":1234}]}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new ClassParser();
        $classes = $classParser($stdClass, $className, $namespace);
        $actual = $classes['Test']['properties'][0]['arrayType'];

        $this->assertEquals('T\\NTest\\Foo', $actual);
    }

    /**
     * @covers ::withNamespacePrefix
     * @uses \Jtp\ClassParser::__construct
     * @uses \Jtp\ClassParser::__invoke
     * @uses \Jtp\ClassParser::parseData
     * @uses \Jtp\ClassParser::parseProperty
     */
    public function testWillGenerateaANamespaceForSubClass()
    {
        $stdClass = json_decode('{"foo":[{"bar":1234}]}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new ClassParser();
        $classParser->withNamespacePrefix('X');
        $classes = $classParser($stdClass, $className, $namespace);
        $actual = $classes['Foo']['classNamespace'];

        $this->assertEquals('T\\XTest', $actual);
    }



    /**
     * @covers ::withNamespacePrefix
     * @uses \Jtp\ClassParser::__construct
     * @uses \Jtp\ClassParser::__invoke
     * @uses \Jtp\ClassParser::parseData
     * @uses \Jtp\ClassParser::parseProperty
     */
    public function testWillGenerateaANamespaceForSubClass2()
    {
        $stdClass = json_decode('{"foo":{"bar":{"baz":1234}}}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new ClassParser();
        $classes = $classParser($stdClass, $className, $namespace);
        $actual = $classes['Bar']['fullName'];

        $this->assertEquals('T\\NTest\\NFoo\\Bar', $actual);
    }
}
