<?php namespace Jtp;

/**
 * Class TemplateDataMassage
 *
 * @package \Jtp
 */
abstract class TemplateDataMassage
{
    /** @var array */
    protected $classMap = [];

    /** @var array */
    protected $map = [];

    /** @var array */
    protected $namespaceMap = [];

    /**
     * @param string $classKey Unique key name used for the class map.
     * @param array $classData Data passed to the template engine.
     * @return array|string
     */
    public function __invoke($classKey, array $classData)
    {
        $classData  = $this->doRenaming($classData);

        return $classData;
    }

    /**
     * @param array $classData
     * @return string
     */
    protected function doRenaming($classData)
    {
        // Rename class.
        $origonalClassName = $classData['name'];
        $classData['name'] = $this->getMappedName($origonalClassName);

        // Rename namespace.
        $classData['classNamespace'] = $this->getMappedName(
            $classData['classNamespace']
        );

        // Rename properties elements.
        $classData['properties'] = $this->renameTypes(
            $origonalClassName,
            $classData['properties']
        );

        return $classData;
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getMappedName($name)
    {
        if (array_key_exists($name, $this->map)) {
            $name = $this->map[$name];
        }

        return $name;
    }

    /**
     * Class properties are stored in the map prefixed with the full class name.
     * This method ensure that only the property name without the prefix will be
     * returned.
     *
     * @param string $name
     * @param string $prefix
     * @return string
     */
    protected function getMappedPropertyName($name, $prefix)
    {
        $key = $prefix . $name;
        if (array_key_exists($key, $this->map)) {
            $name = $this->map[$key];
        }

        return $name;
    }

    /**
     * Class properties are stored in the map prefixed with the full class name.
     * This method ensure that only the property name without the prefix will be
     * returned.
     *
     * @param string $name
     * @return string
     */
    protected function getMappedType($name)
    {
        // Spit at the class name, since in the map, classes do not contain their full name.
        $lastSlash = strrpos($name, '\\');
        $namespace = substr($name, 0, $lastSlash);
        $className = substr($name, ($lastSlash + 1));

        if (array_key_exists($namespace, $this->map)) {
            $namespace = $this->getMappedName($namespace);
        }

        if (array_key_exists($className, $this->map)) {
            $className = $this->getMappedName($className);
        }

        return $namespace . '\\' . $className;
    }

    /**
     * @param string $className
     * @param array $properties
     * @return array
     */
    protected function renameTypes($className, array $properties)
    {
        foreach ($properties as &$property) {
            // Rename property.
            $property['name'] = $this->getMappedPropertyName(
                $property['name'],
                $className . '::$'
            );

            // Rename arrayType.
            if (!empty($property['arrayType'])) {
                $property['arrayType'] = $this->getMappedType(
                    $property['arrayType']
                );
            }

            // Rename paramType.
            if ($property['isCustomType'] && !empty($property['paramType'])) {
                $property['paramType'] = $this->getMappedType(
                    $property['paramType']
                );
            }

            $property['namespace'] = $this->getMappedName(
                $property['namespace']
            );
        }

        return $properties;
    }
}