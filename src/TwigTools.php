<?php
/**
 * @copyright ©2016 Quicken Loans Inc. All rights reserved. Trade
 * Secret, Confidential and Proprietary. Any dissemination outside
 * of Quicken Loans is strictly prohibited.
 */

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
            'setProperty' => new Twig_SimpleFunction(
                'setProperty',
                [$this, 'setProperty']
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
     * @return mixed|string
     */
    public function getFuncType(array $property)
    {
        $output = $property['paramType'] . ' ';

        if ($property['paramType'] == 'array'
            && !empty($property['subType'])) {
            $output = $property['subType'] . ' ';
        } else if (!$this->doScalarTypeHints && is_scalar($property['value'])) {
            // Remove scalar type hints.
            $output = '';
        }

        return $output;
    }

    /**
     * Produce a string of $this->{property} = {value};
     *
     * @param $property
     * @param $value
     * @param $type
     * @return string
     */
    public function setProperty($property, $value, $type)
    {
        $return = '';

        if ($type === 'string') {
            $value = '"' . $value . '"';
        }

        if (!empty($value)) {
            $return = "        " . sprintf('$this->%s = %s;', $property, $value) . "\n";
        }

        return $return;
    }
}
?>
