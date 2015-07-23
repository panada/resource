<?php

namespace Panada\Resources;

use Panada\Resources\Loader;

/**
 * Handler for configuration.
 *
 * @package  Resources
 * @link     http://panadaframework.com/
 * @license  http://www.opensource.org/licenses/bsd-license.php
 * @author   Iskandar Soesman <k4ndar@yahoo.com>
 * @since    Version 1.0.0
 */
class Config
{
    private static $config = [];

    private static function _cache($name)
    {
        $app = Loader::$maps['Controller'];
        
        if (!isset(self::$config[$name])) {
            $array = require $app . 'config/' . $name . '.php';
            self::$config[$name] = $array;
            return $array;
        }
        
        return self::$config[$name];
    }

    /**
     * Handler for user defined config
     */
    public static function __callStatic($name, $arguments = [])
    {
        // Does cache for this config exists?
        if (isset(self::$config[$name])) {
            return self::$config[$name];
        }
        
        $app = Loader::$maps['Controller'];
        
        // Does the config file exists?
        try {
            return self::_cache($name);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
