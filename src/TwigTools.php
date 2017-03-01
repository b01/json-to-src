<?php namespace Jtp;

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
                [$this, 'capFirst']
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
            'getReturnType' => new Twig_SimpleFunction(
                'getReturnType',
                [$this, 'getReturnType']
            ),
            'getVarType' => new Twig_SimpleFunction(
                'getVarType',
                [$this, 'getVarType']
            ),
            'getYear' => new Twig_SimpleFunction(
                'getYear',
                [$this, 'getYear']
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
     * @return mixed|string
     */
    public function getFuncType(array $property)
    {
        $output = $property['paramType'] . ' ';

        if ($property['paramType'] === 'array'
            && !empty($property['arrayType'])) {
            $output = $property['arrayType'] . ' ';
        } else if (!$this->doScalarTypeHints && is_scalar($property['value'])) {
            // Remove scalar type hints.
            $output = '';
        } else if ($property['isCustomType'] === true) {
            // Prefix the namespace to custom types.
            $output = '\\' . $output;
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

        return "\n" . '        $this->' .  $name . ' = ' . $return . ';';
    }

    /**
     * Get the year.
     *
     * @return string
     */
    public function getYear()
    {
        return (string) date('Y');
    }

    /**
     * Produce a string of $this->{property} = {value};
     *
     * @param array $prop
     * @return string
     */
    public function getVarType(array $prop)
    {
        $output = '';

        if (!empty($prop['arrayType'])) {
            $output = '@var '. $prop['paramType'] . ' of \\' . $prop['arrayType'];
        } else if ($prop['isCustomType'] === true) {
            $output = '@var \\' . $prop['paramType'];
        } else if (!empty($prop['paramType'])) {
            $output = '@var ' . $prop['paramType'];
        }

        return $output;
    }


    /**
     * Get the doc-block return type.
     *
     * @param array $prop
     * @return string
     */
    public function getReturnType(array $prop)
    {
        $output = '';

        if (!empty($prop['arrayType'])) {
            $output = $prop['paramType'] . ' of \\' . $prop['arrayType'];
        } else if ($prop['isCustomType'] === true) {
            $output = '\\' . $prop['paramType'];
        } else if (!empty($prop['paramType'])) {
            $output = $prop['paramType'];
        }

        return $output;
    }
    /**
     * Capitalize on the first letter of a word.
     *
     * Wrapper for Twig.
     *
     * @param string $word
     * @return string
     */
    public function capFirst($word)
    {
        return ucfirst($word);
    }
}
?>
