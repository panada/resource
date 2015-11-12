<?php

namespace Panada\Resource;

use Panada\Request\Uri;
use Panada\Resource\Config;
use Panada\Resource\Response;
use Panada\Resource\HTTPException;
use Panada\Router\Routes as Router;

/**
 * @author  kandar <iskandarsoesman@gmail.com>
 * 
 * @link    http://panadaframework.com/
 *
 * @license http://www.opensource.org/licenses/bsd-license.php
 *
 * @since   version 2.0.0
 */
class Gear
{
    public function __construct($errorReporting)
    {
        $this->config   = Config::main();
        $this->uri      = Uri::getInstance(['defaultController' => $this->config['defaultController']]);
        $this->response = Response::getInstance();
        
        // Exception handler
        $exception      = new Exception($this->response);
                
        set_exception_handler([$exception, 'main']);
        set_error_handler([$exception, 'errorHandler'], $errorReporting);
        
        $this->firstUriPath = ucwords($this->uri->getController());
        $this->requstHandler = $this->config['requestHandlerRule'];
        
        $handler = current($this->requstHandler);
        
        $this->$handler();
    }
    
    /**
     * Hendle the controller calling process.
     *
     *  @return void
     */
    private function controllerHandler()
    {
        $controllerNamespace = 'Controller\\' . $this->firstUriPath;

        $action = $this->uri->getAction();
        $request = $this->uri->getRequests();

        try{
            $instance = new $controllerNamespace;
        }
        catch(\Exception $e) {
            
            if($nextHandler = next($this->requstHandler)) {
                $this->$nextHandler();
                
                return;
            }
            else {
                $this->throwHTTPException();
            }
        }
        
        $this->run($instance, $action, $request);
    }
    
    private function moduleHandler()
    {
        $controllerNamespace = 'Module\\'.$this->firstUriPath.'\\Controller';
        
        if(! $controller = $this->uri->getSegment(1)){
            $controller = $this->config['defaultController'];
        }
        
        $controllerNamespace .= '\\'.ucwords($controller);
        
        if(! $action = $this->uri->getSegment(2)){
            $action = $this->config['defaultAction'];
        }
        
        $request = $this->uri->getRequests(3);
        
        try{
            $instance = new $controllerNamespace;
        }
        catch(\Exception $e) {
            if($nextHandler = next($this->requstHandler)) {
                $this->$nextHandler();
                
                return;
            }
            else {
                $this->throwHTTPException();
            }
        }
        
        $this->run($instance, $action, $request);
    }
    
    private function routingHandler()
    {
        $routes = Config::routes();
        
        Router::$patterns = $routes['pattern'];
        Router::$defaults = $routes['defaults'];
        
        foreach($routes['route'] as $name => $route) {
            $class = $route['controller'];
            $method = $route['action'];
            
            unset($route['controller'], $route['action']);
            
            Router::route($name, $route, ['class' => $class, 'method' => $method]);
        }
        
        // match
        if($result = Router::find()) {
            
            try{
                $instance = new $result['class'];
            }
            catch(\Exception $e) {
                if($nextHandler = next($this->requstHandler)) {
                    $this->$nextHandler();
                    
                    return;
                }
                else {
                    $this->throwHTTPException();
                }
            }
            
            $variables = Router::$variables;
            unset($variables['method'], $variables['protocol'], $variables['subdomain'], $variables['domain'], $variables['port']);
            $args = array_values($variables);
            
            $this->run($instance, $result['method'], $args);
        }
        else {
            if($nextHandler = next($this->requstHandler)) {
                $this->$nextHandler();
                
                return;
            }
            else {
                $this->throwHTTPException();
            }
        }
    }
    
    private function throwHTTPException()
    {
        throw new HTTPException('No controller available for GET /'.$this->uri->getPathInfo());
    }
    
    /**
     * Call the controller's method
     *
     * @param  object $instance
     * @param  string $method
     * @param  array  $request
     * @return void
     */
    private function run($instance, $action, $request)
    {
        if(! method_exists($instance, $action)) {
            $request = array_merge([$action], $request);
            $action = $this->config['aliasAction'];
        }
        
        try{
            $this->response->setBody(
                call_user_func_array([$instance, $action], $request)
            );
        }
        catch(\Exception $e) {
            
            if ( substr($e->getMessage(), 0, 20) == 'call_user_func_array' ) {
                throw new HTTPException('No action or routing config available for GET /'.$this->uri->getPathInfo());
            }
            else {            
                throw $e;
            }
        }
    }
    
    public function __toString()
    {
        return $this->response->output();
    }
    
    public static function send($errorReporting = E_ALL)
    {
        try{
            echo new self($errorReporting);
        }
        catch(\Exception $e){
            echo (new Exception(Response::getInstance()))->main($e)->output();
        }
    }
}
