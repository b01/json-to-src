<?php namespace Jtp;

use stdClass;
use Twig_Template;

/**
 * @package \Jtp\Converter
 */
class Converter
{
    /** @var string Regular expression use to verify a class name.*/
    const REGEX_CLASS = '/^[a-zA-Z][a-zA-Z0-9_]*$/';

    /** @var string Regular expression use to verify a name-space.*/
    const REGEX_NS = '/^[a-zA-Z][a-zA-Z0-9\\\\]*[a-zA-Z]?$/';

    /** @var string */
    private $className;

    /**
     * ex: [ "className" => ["name" => "property1", "type" => "integer"], ... ]
     *
     * @var array
     */
    private $classes;

    /** @var Twig_Template */
    private $classTemplate;

    /** @var boolean */
    private $genUnitTests;

    /** @var \stdClass */
    private $json;

    /** @var string */
    private $namespace;

    /** @var integer Limit the amount of recursion for nested objects. */
    private $recursionLimit;

    /**
     * List of strings that represent the PHP source generated from the JSON.
     *
     * @var array
     */
    private $sources;

    /** @var array Source code for unit test. */
    private $unitTests;

    /** @var \Twig_Template */
    private $unitTestTemplate;

    /** @var boolean */
    private static $debug = false;

    /**
     * Indicates debugging is on.
     *
     * @return type
     */
    static public function isDebugOn()
    {
        return self::$debug;
    }

    /**
     * Turn debugging off/on.
     *
     * @param bool $debug
     */
    static public function setDebugMode(bool $debug)
    {
        self::$debug = $debug;
    }

    /**
     * Constructor
     *
     * @param string $jsonString
     * @param string $className
     * @param string $namespace
     * @param int $rLimit
     * @throws JtpException
     */
    public function __construct(
        string $jsonString,
        string $className,
        string $namespace = '',
        int $rLimit = 3
    ) {
        $this->json = json_decode($jsonString);

        if (!$this->json instanceof stdClass) {
            throw new JtpException(
                JtpException::BAD_JSON_DECODE,
                [json_last_error_msg(), $jsonString]
            );
        }

        if (preg_match(self::REGEX_CLASS, $className) !== 1) {
            throw new JtpException(JtpException::BAD_CLASS_NAME, [$className]);
        }

        if (!empty($namespace)
            && preg_match(self::REGEX_NS, $namespace) !== 1) {
            throw new JtpException(JtpException::BAD_NAMESPACE, [$namespace]);
        }

        $this->className = $className;
        $this->namespace = $namespace;
        $this->recursionLimit = $rLimit;
        $this->classes = [];
        $this->sources = [
            'classes' => [],
            'tests' => []
        ];
        $this->typeMap = [
            'boolean' => 'bool',
            'array' => 'array',
            'integer' => 'int',
            'NULL' => ''
        ];

        $this->genUnitTests = true;
        $this->unitTests = [];
    }

    /**
     * Generate PHP source from JSON.
     *
     * @return array Each element is the PHP source.
     */
    public function generateSource()
    {
        $objectVars = get_object_vars($this->json);
        $this->classes = $this->parseClassData($objectVars, $this->className);

        foreach ($this->classes as $className => $properties) {
            $this->sources['classes'][$className] = $this->classTemplate->render([
                'className' => $className,
                'classProperties' => $properties,
                'classNamespace' => $this->namespace
            ]);

            if ($this->genUnitTests && $this->unitTestTemplate instanceof Twig_Template) {
                $this->sources['tests'][$className . 'Test'] = $this->unitTestTemplate->render([
                    'className' => $className,
                    'classProperties' => $properties,
                    'classNamespace' => $this->namespace
                ]);
            }
        }

        return $this->sources;
    }

    /**
     * Save PHP source files to disk.
     *
     * @param string $directory Directory to save the files.
     * @param string $unitTestDir Specify a separate directory for unit tests.
     * @return void
     * @throws \Jtp\JtpException
     */
    public function save($directory, $unitTestDir = null)
    {
        if (!is_writeable($directory)) {
            throw new JtpException(JtpException::NOT_WRITEABLE, [$directory]);
        }

        foreach($this->sources['classes'] as $className => $code) {
            $filename = $directory . DIRECTORY_SEPARATOR . $className . '.php';

            file_put_contents($filename, $code);
        }

        if (!is_dir($unitTestDir)) {
            $unitTestDir = $directory;
        }
        foreach($this->sources['tests'] as $className => $code) {
            $filename = $unitTestDir . DIRECTORY_SEPARATOR . $className . '.php';

            file_put_contents($filename, $code);
        }
    }

    /**
     * Set template to generate class source.
     *
     * @param Twig_Template $template
     */
    public function setClassTemplate(Twig_Template $template)
    {
        $this->classTemplate = $template;
    }

    /**
     * Turn on/off generating unit test class.
     *
     * @param bool $bool
     */
    public function setGenUnitTests(bool $bool)
    {
        $this->genUnitTests = $bool;
    }

    public function setUnitTestTemplate(Twig_Template $template)
    {
        $this->unitTestTemplate = $template;
    }

    /**
     * Parse a JSON field and returns the following as array:
     * * name - the name this values is assigned.
     * * type - scalar, array, or custom class name
     * * isCustomType - Indicate that the type is a user defined  class.
     * * paramType - The string to use in a function signature or doc-block.
     * * value - is_array($value) ? '[]' : $value
     * * arrayType - When the value is an array of objects, this will contain the type.
     *
     * @param array $property
     * @param mixed $value
     * @return array
     */
    private function parseProperty($property, $value)
    {
        $type = gettype($value);
        $isCustomType = $type === 'object';

        if ($isCustomType) {
            $customType = ucfirst($property);
            $type = $customType;
            $paramType = $customType;
        } else {
            $paramType = array_key_exists($type, $this->typeMap)
                ? $this->typeMap[$type]
                : $type;
        }
        $isAnArrayOfObjects = is_array($value)
            && count($value) > 0
            && is_object($value[0]);

        return [
            'name' => str_replace(['$', '-'], '', $property),
            'type' => $type,
            'isCustomType' => $isCustomType,
            'paramType' => $paramType,
            'value' => is_array($value) ? '[]' : $value,
            'arrayType' => $isAnArrayOfObjects ? $property : ''
        ];
    }

    /**
     * Transforms an array representation of a stdClsss into a structured array
     * where each element represents a class and their values represent that
     * classes properties.
     *
     * ex: [ "className" => ["name" => "property1", "type" => "integer"], ... ]
     *
     * A practical example would be an array returned from get_object_vars on
     * stdClass object.
     *
     * * Convert JSON keys that have scalar values into properties. '$' will be removed.
     * * Convert hash arrays into classes.
     * * Convert stdClass objects into classes.
     *
     * @staticvar int $rCount
     * @param array $objectVars
     * @param string $className
     * @param array $classes
     * @return array
     */
    private function parseClassData(
        array $objectVars,
        string $className,
        array & $classes = []
    ) {
        static $rCount = 0;
        // Limit the amount of recursion that can be performed.
        if ($rCount >= $this->recursionLimit) {
            return [];
        }

        // Loop through keys.
        foreach ($objectVars as $key => $value) {
            $keyProps = $this->parseProperty($key, $value);
            $properties[] = $keyProps;

            // Build class from object in an array.
            if (!empty($keyProps['arrayType'])) {
                $value = array_pop($value);
                $key = $keyProps['arrayType'];
            }

            // Build class from a nested object.
            if (is_object($value)) {
                $newClassName = ucfirst($key);
                $newClass = get_object_vars($value);
                $rCount++;
                $this->parseClassData(
                    $newClass,
                    $newClassName,
                    $classes
                );
                $rCount--;
            }
        }

        if (count($properties) > 0) {
            $classes[$className] = $properties;
        }

        if (self::isDebugOn()) {
            $this->debugParseClasses($rCount, $className, $properties);
        }

        return $classes;
    }

    /**
     * Debug method for the recursive class builder method.
     *
     * @param int $rCount
     * @param string $className
     * @param array $properties
     */
    private function debugParseClasses(
        int $rCount,
        string $className,
        array $properties
    ) {
        echo "recursion: $rCount" . PHP_EOL;
        echo "className: $className" . PHP_EOL;
        echo "properties:" . PHP_EOL;
        // display each property.
        foreach ($properties as $property) {
            echo "  {$property['paramType']} {$property['name']}" . PHP_EOL;
        }

        echo PHP_EOL;
    }
}
?>
