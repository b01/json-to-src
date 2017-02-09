<?php namespace Jtp;

/**
 * Class TemplateDataMassage
 *
 * @package \Jtp
 */
abstract class TemplateDataMassage
{
    /** @var array */
    protected $map = [];

    /**
     * @param string $classKey Unique key name used for the class map.
     * @param array $classData Data passed to the template engine.
     * @return array
     */
    public function __invoke($classKey, array $classData)
    {
        return $this->doRemapping($classKey, $classData);
    }

    /**
     * @param string $classKey
     * @param array $classData
     * @return array
     */
    protected function doRemapping($classKey, array $classData)
    {
        // Rename class.
        $classData['name'] = $this->getMappedName(
            $classKey,
            $classData['name']
        );

        // Rename namespace.
        $classData['classNamespace'] = $this->getMappedName(
            $classData['classNamespace'],
            $classData['classNamespace']
        );

        // Rename properties elements.
        $classData['properties'] = $this->renameTypes(
            $classKey,
            $classData['properties']
        );

        return $classData;
    }

    /**
     * Get the mapped name of a class/namespace/property.
     *
     * Class properties are stored in the map prefixed with the full class name.
     * This method ensure that only the property name without the prefix will be
     * returned.
     *
     * @param string $key
     * @param string $name
     * @return string
     */
    protected function getMappedName($key, $name)
    {
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
    protected function getMappedType($classKey, $name)
    {
        // Spit at the class name, since in the map, classes do not contain their full name.
        $lastSlash = strrpos($name, '\\');
        $namespace = substr($name, 0, $lastSlash);
        $className = substr($name, ($lastSlash + 1));

        // Update the namespace when its been renamed.
        if (array_key_exists($namespace, $this->map)) {
            $namespace = $this->getMappedName($namespace, $namespace);
        }

        // Update the class name when its been renamed.
        if (array_key_exists($classKey, $this->map)) {
            $className = $this->getMappedName($classKey, $className);
        }

        // Return it as a whole
        return $namespace . '\\' . $className;
    }

    /**
     * @param string $classKey
     * @param array $properties
     * @return array
     */
    protected function renameTypes($classKey, array $properties)
    {
        foreach ($properties as &$property) {
            // Rename property.
            $key = $classKey . '::$' . $property['name'];

            $property['name'] = $this->getMappedName(
                $key,
                $property['name']
            );

            // Rename arrayType.
            if (!empty($property['arrayType'])) {
                $property['arrayType'] = $this->getMappedType(
                    $classKey,
                    $property['arrayType']
                );
            }

            // Rename paramType.
            if ($property['isCustomType'] && !empty($property['paramType'])) {
                $property['paramType'] = $this->getMappedType(
                    $classKey,
                    $property['paramType']
                );
            }

            $property['namespace'] = $this->getMappedName(
                $property['namespace'],
                $property['namespace']
            );
        }

        return $properties;
    }
}