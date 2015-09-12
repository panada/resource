<?php

namespace Panada\Resource;

/**
 * @author kandar <iskandarsoesman@gmail.com>
 */
class Loader
{
    public static $maps = [];
    private static $psrs = [];
    private static $psrIncluded = [];
    
    public function __construct($maps)
    {
        self::$maps = $maps;
        
        spl_autoload_register([$this, 'init']);
    }
    
    public function init($class)
    {
        $prefix = explode('\\', $class);
        $maps = self::$maps;
        $folder = null;
        
        if( isset($maps[$prefix[0]]) ) {
            $folder = $maps[$prefix[0]];
        }
        
        try{
            include $folder . str_replace(['\\', 'Panada/'.$prefix[1]], ['/', strtolower($prefix[1])], $class) . '.php';
        }
        catch(\Exception $e) {
            if( substr($e->getMessage(), 0, 7) == 'include' ) {
                $this->composerGetMap($class, $prefix);
            }
            else {
                throw $e;
            }
        }
        
    }
    
    /**
     * A better way to autoload composer base file without the need of vendor/autoload.php native file
     */
    private function composerGetMap($class, $prefix)
    {
        // file already included, no need to proceed.
        if( isset(self::$psrIncluded[$class]) ) {
            return;
        }
        
        $prefix = explode('\\', $class);
        
        if(! self::$psrs) {
            self::$psrs['psr4'] = include self::$maps['vendor'].'composer/autoload_psr4.php';
            self::$psrs['ns'] = include self::$maps['vendor'].'composer/autoload_namespaces.php';
            self::$psrs['cm'] = include self::$maps['vendor'].'composer/autoload_classmap.php';
        }
        
        $ns = null;
        
        foreach(explode('\\', $class) as $path) {
            
            $ns .= $path.'\\';
            
            foreach(self::$psrs as $psrType => $folder) {
                
                $method = 'composerAL'.$psrType;
                
                if( isset(self::$psrs[$psrType][$ns]) ) {
                    
                    $this->$method($ns, $folder[$ns], $class, $psrType);
                    
                    return;
                }
                
                $map = trim($ns, '\\');
                
                if( isset(self::$psrs[$psrType][$map]) ) {
                    
                    $this->$method($map, $folder[$map], $class, $psrType);
                    
                    return;
                }
            
            }
        }
        
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3)[2];
        
        throw new \ErrorException('Resource ' . $class . ' not available! please check your class or namespace name.', 0, 1, $trace['file'], $trace['line']);
    }
    
    /**
     * Composer autoload psr4
     */
    private function composerALpsr4($key, $val, $class)
    {
        $class = substr_replace($class, '', 0,strlen($key));
        
        $this->composerALns($key, $val, $class);
    }
    
    /**
     * Composer autoload namespaces
     */
    private function composerALns($key, $val, $class)
    {
        $val    = $val[0];
        $folder = trim($val, '/').'/';
        $file   = str_replace('\\', '/', $class);
        $file   = '/'.$folder.$file.'.php';
                
        $this->composerIncludeFile($file);
    }
    
    /**
     * Composer autoload classmap
     */
    private function composerALcm($key, $val, $class)
    {
        $this->composerIncludeFile($val);
    }
    
    private function composerIncludeFile($file)
    {
        try{
            include $file;
        }
        catch(\Exception $e) {
            if( substr($e->getMessage(), 0, 7) == 'include' ) {
                
                $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3)[2];
        
                throw new \ErrorException('Resource ' . $file . ' not available! please check your class or namespace name. Exception error message is: '.$e->getMessage(), 0, 1, $trace['file'], $trace['line']);
            }
            else {
                throw $e;
            }
        }
    }
}
