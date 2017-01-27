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
     * @param string $className
     * @param array $properties
     * @return array
     */
    protected function renameTypes($className, array $properties)
    {
        foreach ($properties as &$property) {
            // Rename property.
            $key = $className . '::$' . $property['name'];
            $property['name'] = $this->getMappedName($key);

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