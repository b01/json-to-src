<?php namespace Jtp\Tests;

use Jtp\StdClassParser;

/**
 * @coversDefaultClass \Jtp\StdClassParser
 */
class ClassParserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::__construct
     */
    public function testCanInitialize()
    {
        $classParser = new StdClassParser();

        $this->assertInstanceOf(StdClassParser::class, $classParser);
    }

    /**
     * @covers ::__invoke
     * @uses \Jtp\StdClassParser::__construct
     * @uses \Jtp\StdClassParser::parseData
     * @uses \Jtp\StdClassParser::parseProperty
     */
    public function testCanBeInvoked()
    {
        $stdObject = json_decode('{"foo":null}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new StdClassParser();
        $sources = $classParser($stdObject, $className, $namespace);

        $this->assertArrayHasKey('Test', $sources);
    }

    /**
     * @covers ::parseData
     * @covers ::parseProperty
     * @uses \Jtp\StdClassParser::__construct
     * @uses \Jtp\StdClassParser::__invoke
     */
    public function testCanGenSubObjects()
    {
        $stdClass = json_decode('{"prop":[{"o2prop": 1234}]}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new StdClassParser();
        $sources = $classParser($stdClass, $className, $namespace);

        $this->assertArrayHasKey('Prop', $sources);
    }

    /**
     * @covers ::parseProperty
     * @uses \Jtp\StdClassParser::__construct
     * @uses \Jtp\StdClassParser::__invoke
     * @uses \Jtp\StdClassParser::parseData
     */
    public function testWillDefaultArrayPropertiesToEnptyArray()
    {
        $stdClass = json_decode('{"prop":[]}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new StdClassParser();
        $sources = $classParser($stdClass, $className, $namespace);
        $actual = $sources['Test']['properties'][0];

        $this->assertEquals('array', $actual['type']);
        $this->assertEquals('[]', $actual['value']);
    }

    /**
     * @covers ::parseProperty
     * @uses \Jtp\StdClassParser::__construct
     * @uses \Jtp\StdClassParser::__invoke
     * @uses \Jtp\StdClassParser::parseData
     */
    public function testWillDefaultStringPropertiesToAnEmptyString()
    {
        $stdClass = json_decode('{"prop":"It\'s me"}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new StdClassParser();
        $sources = $classParser($stdClass, $className, $namespace);
        $actual = $sources['Test']['properties'][0];

        $this->assertEquals('string', $actual['type']);
        $this->assertEquals('It\\\'s me', $actual['value']);
    }

    /**
     * @covers ::withAccessLevel
     * @uses \Jtp\StdClassParser::__construct
     * @uses \Jtp\StdClassParser::parseProperty
     * @uses \Jtp\StdClassParser::parseData
     */
    public function testCanChangeAccessLevelToSomethingOtherThanTheDefaultForProperties()
    {
        $stdClass = json_decode('{"prop":"It\'s me"}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new StdClassParser();

        $classParser->withAccessLevel('public');

        $sources = $classParser($stdClass, $className, $namespace);
        $actual = $sources['Test']['properties'][0]['access'];

        $this->assertEquals('public', $actual);
    }

    /**
     * @covers ::withAccessLevel
     * @uses \Jtp\StdClassParser::__construct
     * @uses \Jtp\StdClassParser::parseProperty
     * @uses \Jtp\StdClassParser::parseData
     * @expectedException \Jtp\JtpException
     *
     */
    public function testCannotSetAccessLevelToInvalidValue()
    {
        $stdClass = json_decode('{"prop":"It\'s me"}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new StdClassParser();

        $classParser->withAccessLevel('level');
    }

    /**
     * @covers ::withAllowedAccessLevels
     * @uses \Jtp\StdClassParser::__construct
     * @uses \Jtp\StdClassParser::__invoke
     * @uses \Jtp\StdClassParser::parseProperty
     * @uses \Jtp\StdClassParser::parseData
     * @uses \Jtp\StdClassParser::withAccessLevel
     */
    public function testCanSetWhatAccessLevelsAreAllowed()
    {
        $stdClass = json_decode('{"prop":"It\'s me"}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new StdClassParser();

        $classParser->withAllowedAccessLevels(['test'])
            ->withAccessLevel('test');

        $sources = $classParser($stdClass, $className, $namespace);
        $actual = $sources['Test']['properties'][0]['access'];

        $this->assertEquals('test', $actual);
    }

    /**
     * @covers ::debugParseClasses
     * @covers ::parseData
     * @uses \Jtp\StdClassParser::__construct
     * @uses \Jtp\StdClassParser::__invoke
     * @uses \Jtp\StdClassParser::parseProperty
     * @uses \Jtp\Debug::isDebugOn
     * @uses \Jtp\Debug::setDebugMode
     */
    public function testCanEnableDebugging()
    {
        // Must be turned off after function completes.
        StdClassParser::setDebugMode(true);

        $stdClass = json_decode('{"prop":1234}');
        $className = 'Test';
        $classParser = new StdClassParser();

        $this->setOutputCallback(function ($actual) {
            $message = "recursion: 0" . PHP_EOL
                . "fullName: \Test" . PHP_EOL
                . "properties:" . PHP_EOL
                . "  int prop" . PHP_EOL . PHP_EOL;
            $this->assertEquals($message, $actual);
        });

        $classParser($stdClass, $className);

        StdClassParser::setDebugMode(false);
    }

    /**
     * @covers ::getIncrementalClassName
     * @uses \Jtp\StdClassParser::__construct
     * @uses \Jtp\StdClassParser::__invoke
     * @uses \Jtp\StdClassParser::parseData
     * @uses \Jtp\StdClassParser::parseProperty
     */
    public function testCanExtractAClassFromAnArrayOfObjectsWithinANestedObject()
    {
        $stdObject = json_decode('{"foo":{"foo":[{"baz":1234}]}}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new StdClassParser();
        $sources = $classParser($stdObject, $className, $namespace);

        $this->assertArrayHasKey('Foo_1', $sources);
    }

    /**
     * @covers ::getIncrementalClassName
     * @uses \Jtp\StdClassParser::__construct
     * @uses \Jtp\StdClassParser::__invoke
     * @uses \Jtp\StdClassParser::parseData
     * @uses \Jtp\StdClassParser::parseProperty
     */
    public function testCanAppendNumberToClassNameToPreventCollision()
    {
        $stdClass = json_decode('{"location":{"foo":1234, "location":{"bar":1234}}}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new StdClassParser();
        $sources = $classParser($stdClass, $className, $namespace);

        $this->assertArrayHasKey('Location_1', $sources);
    }

    /**
     * @covers ::parseProperty
     * @uses \Jtp\StdClassParser::__construct
     * @uses \Jtp\StdClassParser::__invoke
     * @uses \Jtp\StdClassParser::parseData
     */
    public function testCanParseAClassFromPropertyThatIsAnObject()
    {
        $stdObject = json_decode('{"prop":1234, "test2":{"prop2":1234}}');
        $className = 'Test';
        $classParser = new StdClassParser();
        $actual = $classParser($stdObject, $className);

        $this->assertArrayHasKey('Test2', $actual);
    }

    /**
     * @covers ::parseData
     * @uses \Jtp\StdClassParser::__construct
     * @uses \Jtp\StdClassParser::__invoke
     * @uses \Jtp\StdClassParser::parseProperty
     */
    public function testWillNotGoOverRecursionLimit()
    {
        $stdClass = json_decode('{"prop":1234, "test2":{"prop2":1234, "test3":{"prop":1234, "test4":{"prop":1234}}}}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new StdClassParser();
        $actual = $classParser($stdClass, $className, $namespace);

        $this->assertArrayNotHasKey('Test4', $actual);
    }

    /**
     * @covers ::parseProperty
     * @uses \Jtp\StdClassParser::__construct
     * @uses \Jtp\StdClassParser::__invoke
     * @uses \Jtp\StdClassParser::parseData
     */
    public function testWillUppercaseArrayType()
    {
        $stdClass = json_decode('{"foo":[{"bar":1234}]}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new StdClassParser();
        $classes = $classParser($stdClass, $className, $namespace);
        $actual = $classes['Test']['properties'][0]['arrayType'];

        $this->assertEquals('T\\NTest\\Foo', $actual);
    }

    /**
     * @covers ::withNamespacePrefix
     * @uses \Jtp\StdClassParser::__construct
     * @uses \Jtp\StdClassParser::__invoke
     * @uses \Jtp\StdClassParser::parseData
     * @uses \Jtp\StdClassParser::parseProperty
     */
    public function testWillGenerateaANamespaceForSubClass()
    {
        $stdClass = json_decode('{"foo":[{"bar":1234}]}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new StdClassParser();
        $classParser->withNamespacePrefix('X');
        $classes = $classParser($stdClass, $className, $namespace);
        $actual = $classes['Foo']['classNamespace'];

        $this->assertEquals('T\\XTest', $actual);
    }



    /**
     * @covers ::withNamespacePrefix
     * @uses \Jtp\StdClassParser::__construct
     * @uses \Jtp\StdClassParser::__invoke
     * @uses \Jtp\StdClassParser::parseData
     * @uses \Jtp\StdClassParser::parseProperty
     */
    public function testWillGenerateaANamespaceForSubClass2()
    {
        $stdClass = json_decode('{"foo":{"bar":{"baz":1234}}}');
        $className = 'Test';
        $namespace = 'T';
        $classParser = new StdClassParser();
        $classes = $classParser($stdClass, $className, $namespace);
        $actual = $classes['Bar']['fullName'];

        $this->assertEquals('T\\NTest\\NFoo\\Bar', $actual);
    }
}
