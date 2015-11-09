<?php

namespace Panada\Resource;

/**
 * @author  kandar <iskandarsoesman@gmail.com>
 * @link    http://panadaframework.com/
 * @license http://www.opensource.org/licenses/bsd-license.php
 * @since   version 2.0.0
 * @package Resource
 * 
 */
class Response extends \Panada\Utility\Factory
{
    public static $statusCode = 200;
    public static $headers = [
        'Content-Type' => 'text/html; charset=utf-8',
    ];
    
    private static $body;
    private static $statusText = 'OK';
    private static $cookies = [];
    
    private static $status = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-status',
            208 => 'Already Reported',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => 'Switch Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Time-out',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Large',
            415 => 'Unsupported Media Type',
            416 => 'Requested range not satisfiable',
            417 => 'Expectation Failed',
            418 => 'I\'m a teapot',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            425 => 'Unordered Collection',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Time-out',
            505 => 'HTTP Version not supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            508 => 'Loop Detected',
            511 => 'Network Authentication Required',
        ];
    
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
        self::$body = $body;
    }
    
    public function getBody()
    {
        return self::$body;
    }
    
    public function setHeaders($key, $value)
    {
        self::$headers[$key] = $value;
    }
    
    public function getStatusCode()
    {
        return self::$statusCode;
    }
    
    public function setStatusCode($statusCode)
    {
        self::$statusCode = $statusCode;
        self::$statusText = self::$status[$statusCode];
    }
    
    public function setStatus($statusText, $statusCode = 200)
    {
        self::$statusText = $statusText;
        self::$statusCode = $statusCode;
    }
    
    public function redirect($location, $statusCode = 302)
    {
        if (substr($location,0,4) != 'http') {
            $location = \Panada\Request\Uri::getInstance()->location(ltrim($location, '/'));
        }
        
        self::$statusText = 'Location: '.$location;
        self::$statusCode = $statusCode;
        self::$body = '<html><head><meta http-equiv="refresh" content="0; url='.$location.'" /></head><body></body></html>';
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
        
        
        return self::$body;
    }
}
