<?php namespace Jtp;

use stdClass;

/**
 * Parse a stdClass into an array the can be passed trough
 */
class StdClassParser
{
    use Debug;

    /** @var string The default access level for generated class property. */
    private $accessLevel;

    /** @var array The allowed access levels for the source code. */
    private $allowedAccessLevels;

    /** @var string A prefix added to nested classes to prevent class collision. */
    private $namespacePrefix;

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
        $this->namespacePrefix = 'N';
    }

    /**
     * Gen an array that breaks down a standard class object into type information.
     *
     * @param string $className
     * @param \stdClass $stdClass
     * @return array
     */
    public function __invoke(stdClass $stdClass, $className, $namespace = '')
    {
        $objectVars = get_object_vars($stdClass);
        $classTypeData = [];

        $this->parseData($objectVars, $className, $classTypeData, $namespace);

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
     * Set a prefix to generated namespaces. The default is "N".
     *
     * @param string $prefix
     */
    public function withNamespacePrefix($prefix)
    {
        $this->namespacePrefix = $prefix;
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
     * @param string $namespace
     * @param string $subNamespace
     * @return array
     */
    private function parseProperty(
        $property,
        $value,
        $namespace = '',
        $subNamespace = ''
    ) {
        $type = gettype($value);
        $isCustomType = $type === 'object';
        $arrayType = '';

        if ($isCustomType) {
            $type = ucfirst($property);
            $paramType = empty($namespace) ? $type : $namespace . '\\' . $type;
        } else {
            $paramType = array_key_exists($type, $this->typeMap)
                ? $this->typeMap[$type]
                : $type;
        }

        if (is_array($value) && count($value) > 0 && is_object($value[0])) {
            $arrayType = empty($subNamespace) ? ucfirst($property)
                : $subNamespace . '\\' . ucfirst($property);
        }

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
            'arrayType' => $arrayType,
            'namespace' => $namespace
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
        array & $classes,
        $namespace = ''
    ) {
        static $rCount = 0;
        static $aryType = [];

        // Limit the amount of recursion that can be performed.
        if ($rCount >= $this->recursionLimit) {
            return $classes;
        }

        // Build a unique namespace for the nested class.
        $subNamespace = $namespace . '\\' . $this->namespacePrefix . $className;

        // Loop through keys.
        foreach ($objectVars as $key => $value) {
            $keyProps = $this->parseProperty($key, $value, $namespace, $subNamespace);

            // Build class from object in an array.
            if (!empty($keyProps['arrayType'])) {
                $value = array_pop($value);
            }

            // Build class from a nested object.
            if (is_object($value)) {
                $subClassName = ucfirst($key);
                $subClassVars = get_object_vars($value);

                if (count($subClassVars) > 0) {
                    $rCount++;
                    $this->parseData(
                        $subClassVars,
                        $subClassName,
                        $classes,
                        $subNamespace
                    );

                    if (array_key_exists($rCount, $aryType)) {
                        $keyProps['arrayTypeClassKey'] = $aryType[$rCount];
                        unset($aryType[$rCount]);
                    }

                    $rCount--;
                }
            }

            $properties[] = $keyProps;
        }

        if (count($properties) > 0) {
            // Generate a unique namespace to prevent classes from being overwritten.
            $classKey = $this->getIncrementalClassName($className, $classes);
            $classes[$classKey] = [
                'name' => $className,
                'classNamespace' => $namespace,
                'properties' => $properties
            ];
            //
            $aryType[$rCount] = $classKey;

            if (self::isDebugOn()) {
                $this->debugParseClasses($rCount, $properties);
            }
        }

        return $classes;
    }

    /**
     * Debug method for the recursive class builder method.
     *
     * @param int $rCount
     * @param array $properties
     */
    private function debugParseClasses(
        $rCount,
        array $properties
    ) {
        echo "recursion: {$rCount}" . PHP_EOL;
        echo "properties:" . PHP_EOL;
        // display each property.
        foreach ($properties as $property) {
            $arrayType = !empty($property['arrayType']) ? '<' . ucfirst($property['arrayType']) . '>' : '';
            echo "  {$property['paramType']}{$arrayType} {$property['name']}" . PHP_EOL;
        }

        echo PHP_EOL;
    }
}
