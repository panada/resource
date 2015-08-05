<?php

namespace Panada\Resource;

class Response extends \Panada\Utilities\Factory
{
    private static $headers = [];
    private static $body;
    private static $statusText;
    private static $statusCode;
    private static $cookies = [];
    
    /**
     * A portion of this method originally taken from Symfony\Component\HttpFoundation\Cookie::__construct()
     *
     * @link https://github.com/symfony/HttpFoundation/blob/master/Cookie.php
     */
    public function setCookie($name, $value = null, $expire = 0, $path = '/', $domain = null, $secure = false, $httpOnly = true)
    {
        // from PHP source code
        if (preg_match("/[=,; \t\r\n\013\014]/", $name)) {
            throw new \ErrorException(sprintf('The cookie name "%s" contains invalid characters.', $name));
        }
        
        if (empty($name)) {
            throw new \ErrorException('The cookie name cannot be empty.');
        }
        
        // convert expiration time to a Unix timestamp
        if ($expire instanceof \DateTime) {
            $expire = $expire->format('U');
        } elseif (!is_numeric($expire)) {
            $expire = strtotime($expire);
            if (false === $expire || -1 === $expire) {
                throw new \ErrorException('The cookie expiration time is not valid.');
            }
        }
        
        $path       = empty($path) ? '/' : $path;
        $secure     = (bool) $secure;
        $httpOnly   = (bool) $httpOnly;
        
        self::$cookies[] = [
            'name'      => $name,
            'value'     => $value,
            'expire'    => $expire,
            'path'      => $path,
            'domain'    => $domain,
            'secure'    => $secure,
            'httpOnly'  => $httpOnly
        ];
    }
    
    public function setBody($body)
    {
        self::$body .= $body;
    }
    
    public function getBody()
    {
        return self::$body;
    }
    
    public function setHeaders($key, $value)
    {
        self::$headers[$key] = $value;
    }
    
    public function setStatus($statusText, $statusCode = 200)
    {
        self::$statusText = $statusText;
        self::$statusCode = $statusCode;
    }
    
    public function redirect($location, $statusCode = 302)
    {
        if ( substr($location,0,4) != 'http' )
            $location = \Panada\Request\Uri::getInstance()->location(ltrim($location, '/'));
        
        self::$statusText = 'Location: '.$location;
        self::$statusCode = $statusCode;
    }
    
    public function getHeaders()
    {
        return self::$headers;
    }
    
    public function output()
    {
        if( headers_sent() ) {
            foreach( headers_list() as $header ) {
                header($header);
            }
            
            http_response_code(http_response_code());
        }
        
        if(self::$statusText && self::$statusCode) {
            header(self::$statusText, true, self::$statusCode);
        }
        
        foreach(self::$headers as $ky => $value) {
            header($ky.': '.$value);
        }
        
        foreach(self::$cookies as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httpOnly']
            );
        }
        
        
        echo self::$body;
    }
}
