<?php namespace Jtp;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */



/**
 * Description of Debug
 */
trait Debug
{
    /** @var boolean */
    private static $debug = false;

    /**
     * Indicates debugging is on.
     *
     * @return type
     */
    static public function isDebugOn()
    {
        return self::$debug;
    }

    /**
     * Turn debugging off/on.
     *
     * @param bool $debug
     */
    static public function setDebugMode($debug)
    {
        self::$debug = $debug;
    }
}
