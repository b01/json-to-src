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

    /** @var string The default access level for generated class property. */
    private $accessLevel;

    /** @var array The allowed access levels for the source code. */
    private $allowedAccessLevels;

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

    /**
     * Function to receive render data before render for alteration.
     *
     * @var callable
     */
    private $preRenderCallback;

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
    static public function setDebugMode($debug)
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
        $jsonString,
        $className,
        $namespace = '',
        $rLimit = 3
    ) {
        $this->json = $this->getRootObject($jsonString);

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
        $this->allowedAccessLevels = [
            'private',
            'protected',
            'public'
        ];
        $this->accessLevel = $this->allowedAccessLevels[0];
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
        $doCallback = is_callable($this->preRenderCallback);

        foreach ($this->classes as $className => $properties) {
            $testData = $renderData = $data = [
                'className' => $className,
                'classProperties' => $properties,
                'classNamespace' => $this->namespace
            ];

            if ($doCallback) {
                $renderData = ($this->preRenderCallback)($renderData, false);
            }

            $this->sources['classes'][$className] = $this->classTemplate->render($renderData);

            if ($this->genUnitTests && $this->unitTestTemplate instanceof Twig_Template) {
                if ($doCallback) {
                    $testData = ($this->preRenderCallback)($testData, true);
                }

                $this->sources['tests'][$className . 'Test']
                    = $this->unitTestTemplate->render($testData);
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

        if (!is_dir($unitTestDir) || !is_writeable($directory)) {
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
     * @return Converter
     */
    public function setClassTemplate(Twig_Template $template)
    {
        $this->classTemplate = $template;

        return $this;
    }

    /**
     * Turn on/off generating unit test class.
     *
     * @param bool $bool
     * @return Converter
     */
    public function setGenUnitTests($bool)
    {
        $this->genUnitTests = $bool;

        return $this;
    }

    /**
     * Set the template to generate unit test.
     *
     * @param \Twig_Template $template
     * @return Converter
     */
    public function setUnitTestTemplate(Twig_Template $template)
    {
        $this->unitTestTemplate = $template;

        return $this;
    }

    /**
     * Set the default access level for class properties.
     *
     * @param $level
     * @return Converter
     * @throws JtpException
     */
    public function withAccessLevel($level)
    {
        if (!in_array($level, $this->allowedAccessLevels)) {
            throw new JtpException(
                JtpException::BAD_ACCESS_LEVEL,
                [$level, print_r($this->allowedAccessLevels, true)]
            );
        }

        $this->accessLevel = $level;

        return $this;
    }

    /**
     * Set the access levels allowed for the generated source.
     *
     * @param array $allowedAccessLevels
     * @return \Jtp\Converter
     */
    public function withAllowedAccessLevels(array $allowedAccessLevels)
    {
        $this->allowedAccessLevels = $allowedAccessLevels;

        return $this;
    }

    /**
     * Set a function to call before rendering the source code.
     *
     * The callable will be passed the render data, and a boolean value to
     * indicate "TRUE" when generating code for a unit test and "FALSE" for a
     * actual class.
     *
     * @return Converter
     * @param callable $callable
     * @return \Jtp\Converter
     */
    public function withPreRenderCallback(callable $callable)
    {
        $this->preRenderCallback = $callable;

        return $this;
    }

    /**
     * Get object from JSON string.
     *
     * Verify the JSON contains an object or an array where the first elements is
     * an object.
     *
     * @param string $jsonString
     * @return bool
     */
    private function getRootObject($jsonString)
    {
        $decoded = json_decode($jsonString);
        $object = null;

        if (is_object($decoded)) {
            $object = $decoded;
        } else if (is_array($decoded)
            && count($decoded) > 0
            && is_object($decoded[0])) {
            $object = $decoded[0];
        }

        return $object;
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
            $type = ucfirst($property);
            $paramType = $type;
        } else {
            $paramType = array_key_exists($type, $this->typeMap)
                ? $this->typeMap[$type]
                : $type;
        }

        $isAnArrayOfObjects = is_array($value)
            && count($value) > 0
            && is_object($value[0]);

        if (is_array($value)) {
            $val = '[]';
        } else if (is_string($value)) {
            // Place a '\' before: \ '
            $val = str_replace(
                ['\\', '\''],
                ['\\\\', '\\\''],
                $value
            );
        } else {
            $val = $value;
        }

        return [
            'access' => $this->accessLevel,
            'name' => str_replace(['$', '-'], '', $property),
            'type' => $type,
            'isCustomType' => $isCustomType,
            'paramType' => $paramType,
            'value' => $val,
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
        $className,
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
                $newObjectVars = get_object_vars($value);
                if (count($newObjectVars) > 0) {
                    $rCount++;
                    $this->parseClassData(
                        $newObjectVars,
                        $newClassName,
                        $classes
                    );
                    $rCount--;
                }
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
        $className,
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
