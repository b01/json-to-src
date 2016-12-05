<?php

namespace Jtp;

use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * Class TwigTools
 *
 * @package \Jtp
 */
class TwigTools extends Twig_Extension
{
    /** @var bool */
    private $doScalarTypeHints;

    /**
     * TwigTools constructor.
     *
     * @param bool $doScalarTypeHints
     */
    public function __construct($doScalarTypeHints = false)
    {
        $this->doScalarTypeHints = $doScalarTypeHints;
    }

    /**
     * Get filters to extend Twig.
     */
    public function getFilters()
    {
        return [
            'ucfirst' => new Twig_SimpleFilter(
                'ucfirst',
                function ($arg) {
                    return ucfirst($arg);
                }
            )
        ];
    }

    /**
     * Get function to extend Twig.
     */
    public function getFunctions()
    {
        return [
            'getAssignProp' => new Twig_SimpleFunction(
                'getAssignProp',
                [$this, 'getAssignProp']
            ),
            'getFullNameSpace' => new Twig_SimpleFunction(
                'getFullNameSpace',
                [$this, 'getFullNameSpace']
            ),
            'getFuncType' => new Twig_SimpleFunction(
                'getFuncType',
                [$this, 'getFuncType']
            ),
            'getPropStmt' => new Twig_SimpleFunction(
                'getPropStmt',
                [$this, 'getPropStmt']
            ),
            'getVarType' => new Twig_SimpleFunction(
                'getVarType',
                [$this, 'getVarType']
            ),
            'getYear' => new Twig_SimpleFunction(
                'getYear',
                function () {
                    return (string)date('Y');
                }
            )
        ];
    }

    /**
     * Return the correct assign format for a property.
     *
     * @param array $property
     * @return mixed|string
     */
    public function getAssignProp(array $property)
    {
        $output = $property['name'];

        if (!empty($property['arrayType'])) {
            $output .= '[]';
        }

        return $output;
    }

    /**
     *
     * @param $namespace
     * @param $className
     * @return mixed
     */
    public function getFullNameSpace($namespace, $className)
    {
        $value = $className;

        if (!empty($namespace)) {
            $value = $namespace . '\\' . $className;
        }

        return $value;
    }

    /**
     * Get the function parameter type.
     *
     * @param array $property
     * @param string $namespace
     * @return mixed|string
     */
    public function getFuncType(array $property, $namespace = '')
    {
        $output = $property['paramType'] . ' ';

        if ($property['paramType'] == 'array'
            && !empty($property['arrayType'])) {
            $output = $property['arrayType'] . ' ';
        } else if (!$this->doScalarTypeHints && is_scalar($property['value'])) {
            // Remove scalar type hints.
            $output = '';
        }

        // Prefix the namespace to custom types.
        if ($property['isCustomType'] && !empty($namespace)) {
            $output = '\\' . $namespace . '\\' . $output;
        }

        return $output;
    }

    /**
     * Produce a string of $this->{property} = {value};
     *
     * @param array $prop
     * @param string $value
     * @param string $type
     * @return string
     */
    public function getPropStmt($prop)
    {
        $return = 'null';
        $type = $prop['type'];
        $value = $prop['value'];
        $name = $prop['name'];

        if ($type === 'string') {
            $return = "'{$value}'";
        }

        if ($type === 'array') {
            $return = '[]';
        }

        return PHP_EOL . '        $this->' .  $name . ' = ' . $return . ';';
    }

    /**
     * Produce a string of $this->{property} = {value};
     *
     * @param array $prop
     * @param string $namespace
     * @return string
     */
    public function getVarType(array $prop, $namespace = '')
    {
        $output = '';

        if (!empty($prop['arrayType'])) {
            $output = '@var '. $prop['paramType'];// . ' of \\' . $namespace . '\\' . $prop['arrayType'];
            if (!empty($namespace)) {
                $output .= ' of \\' .$namespace;
            }
            $output .= '\\' . $prop['arrayType'];
        } else if ($prop['isCustomType'] === true) {
            $output = '@var ';
            if (!empty($namespace)) {
                $output .= '\\' . $namespace;
            }
            $output .= '\\' . $prop['paramType'];
        } else if (!empty($prop['paramType'])) {
            $output = '@var ' . $prop['paramType'];
        }

        return $output;
    }
}
?>
