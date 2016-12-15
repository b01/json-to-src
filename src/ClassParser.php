<?php namespace Jtp;

use stdClass;

/**
 * Parse a stdClass into an array the can be passed trough
 */
class ClassParser
{
    use Debug;

    /** @var string The default access level for generated class property. */
    private $accessLevel;

    /** @var array The allowed access levels for the source code. */
    private $allowedAccessLevels;

    /** @var integer Limit the amount of recursion for nested objects. */
    private $recursionLimit;

    /**
     * @var array Map a type returned from getType to a type that is
     * acceptable in a function parameter.
     */
    private $typeMap;

    /**
     * Constructor
     *
     * @param int $rLimit Controls how many levels deep to go to retrieve
     * classes.
     */
    public function __construct($rLimit = 3)
    {
        $this->recursionLimit = $rLimit;
        $this->typeMap = [
            'boolean' => 'bool',
            'array' => 'array',
            'integer' => 'int',
            'NULL' => ''
        ];
        $this->allowedAccessLevels = [
            'private',
            'protected',
            'public'
        ];
        $this->accessLevel = $this->allowedAccessLevels[0];
    }

    /**
     * Gen an array that breaks down a standard class object into type information.
     *
     * @param string $className
     * @param \stdClass $stdClass
     * @return array
     */
    public function __invoke(stdClass $stdClass, $className, $namspace = '')
    {
        $objectVars = get_object_vars($stdClass);
        $classTypeData = [];

        $this->parseData($objectVars, $className, $classTypeData);

        return $classTypeData;
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
     * @return Converter
     */
    public function withAllowedAccessLevels(array $allowedAccessLevels)
    {
        $this->allowedAccessLevels = $allowedAccessLevels;

        return $this;
    }

    /**
     * @param string $name
     * @param array $classes
     * @return string
     */
    private function getIncrementalClassName($name, array & $classes)
    {
        $nextName = $name;
        $i = 0;

        while (array_key_exists($nextName, $classes)) {
            $i++;
            $nextName = "{$name}_{$i}";
        }

        return $nextName;
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
     * @staticvar integer $rCount
     * @param array $objectVars
     * @param string $className
     * @param array $classes
     * @return array
     */
    private function parseData(
        array $objectVars,
        $className,
        array & $classes
    ) {
        static $rCount = 0;

        // Limit the amount of recursion that can be performed.
        if ($rCount >= $this->recursionLimit) {
            return $classes;
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
                    $this->parseData(
                        $newObjectVars,
                        $newClassName,
                        $classes
                    );
                    $rCount--;
                }
            }
        }

        if (count($properties) > 0) {
            // Patch to prevent classes from being overwritten.
            $className = $this->getIncrementalClassName($className, $classes);
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
        $rCount,
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
