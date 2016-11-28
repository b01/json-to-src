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
 * @package Jtp
 */
class TwigTools extends Twig_Extension
{
    /**
     * @inheritdoc
     */
    public function getFilters()
    {
        return [
            'print_r' => new Twig_SimpleFilter('isSelected',
                function ($arg1) {
                    return print_r($arg1, true);
                }
            ),
            'ucfirst' => new Twig_SimpleFilter(
                'ucfirst',
                function ($arg) {
                    return ucfirst($arg);
                }
            )
        ];
    }

    /**
     * @inheritdoc
     */
    public function getFunctions()
    {
        return [
            'getYear' => new Twig_SimpleFunction(
                'getYear',
                function () {
                    return (string)date('Y');
                }
            ),
            'getFullNameSpace' => new Twig_SimpleFunction(
                'getFullNameSpace',
                [$this, 'getFullNameSpace']
            ),
            'setProperty' => new Twig_SimpleFunction(
                'setProperty',
                [$this, 'setProperty']
            )
        ];
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
