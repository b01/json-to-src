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
    protected $namespaceMap = [];

    /**
     * @param string $classKey Unique key name used for the class map.
     * @param array $classData Data passed to the template engine.
     * @return array|string
     */
    public function __invoke($classKey, array $classData)
    {
        $classData  = $this->doRenaming($classKey, $classData);

        return $classData;
    }

    /**
     * @param string $classKey
     * @param array $classData
     * @return string
     */
    protected function doRenaming($classKey, $classData)
    {
        if (array_key_exists($classKey, $this->classMap)) {
            $classData['name'] = $this->classMap[$classKey];

            $classData['classNamespace'] = $this->renameNamespace(
                $classData['classNamespace']
            );

            $classData['fullName'] = empty($classData['classNamespace'])
                ? $classData['name']
                : $classData['classNamespace'] . '\\' . $classData['name'];

            $classData['properties'] = $this->renameProperties(
                $classKey,
                $classData['properties']
            );
        }

        return $classData;
    }

    /**
     * @param string $namespace
     * @return string
     */
    protected function renameNamespace($namespace)
    {
        if (array_key_exists($namespace, $this->namespaceMap)) {
            $namespace = $this->namespaceMap[$namespace];
        }

        return $namespace;
    }

    /**
     * @param string $classKey
     * @param array $properties
     * @return array
     */
    protected function renameProperties($classKey, array $properties)
    {
        foreach ($properties as &$property) {
            $key = $classKey . '::$' . $property['name'];

            if (array_key_exists($key, $this->classMap)) {
                $property['name'] = $this->classMap[$key];
                $property['namespace'] = $this->renameNamespace(
                    $property['namespace']
                );
            }
        }

        return $properties;
    }
}