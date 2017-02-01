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
        $classData['name'] = $this->getMappedName($classData['name']);

        // Rename namespace.
        $classData['classNamespace'] = $this->getMappedName(
            $classData['classNamespace']
        );

        // Rename properties elements.
        $classData['properties'] = $this->renameTypes(
            $classData['name'],
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
            if (array_key_exists('arrayType', $property)) {
                $property['arrayType'] = $this->getMappedName(
                    $property['arrayType']
                );
            }

            // Rename paramType.
            if ($property['isCustomType'] && array_key_exists('paramType', $property)) {
                $property['paramType'] = $this->getMappedName(
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