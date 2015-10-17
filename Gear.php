<?php

namespace Panada\Resource;

use Panada\Request\Uri;
use Panada\Router\Routes;
use Panada\Resource\Config;
use Panada\Resource\Response;
use Panada\ResourceHTTPException;
use Panada\Resource\HTTPException;

class Gear
{
    private $body;
    
    public function __construct($errorReporting)
    {
        $this->uri      = Uri::getInstance();
        $this->response = Response::getInstance();
        
        // Exception handler
        $exception = new Exception($this->response);
                
        set_exception_handler([$exception, 'main']);
        set_error_handler([$exception, 'errorHandler'], $errorReporting);
        
        $this->firstUriPath = ucwords($this->uri->getController());
        
        $this->controllerHandler();
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
            $this->moduleHandler();
            
            return;
        }
        
        $this->run($instance, $action, $request);
    }
    
    private function moduleHandler()
    {
        $controllerNamespace = 'Module\\'.$this->firstUriPath.'\\Controller';
        
        if(! $controller = $this->uri->getSegment(1)){
            $controller = 'Home';
        }
        
        $controllerNamespace .= '\\'.ucwords($controller);
        
        if(! $action = $this->uri->getSegment(2)){
            $action = 'index';
        }
        
        $request = $this->uri->getRequests(3);
        
        try{
            $instance = new $controllerNamespace;
        }
        catch(\Exception $e) {
            $this->routingHandler();
            
            return;
        }
        
        $this->run($instance, $action, $request);
    }
    
    private function routingHandler()
    {
        Config::routes();
            
        $route = Routes::getInstance()
            ->parse(
                $this->uri->getRequestMethod(),
                '/'.$this->uri->getPathInfo()
            );
        
        if($route) {
            try{
                $this->run(new $route['controller'], $route['action'], $route['args']);
            }
            catch(\Exception $e) {
                throw new HTTPException('Routing for GET /'.$this->uri->getController().' is available but no controller or method can handle it. Please check your routing config.');
            }
        }
        else {
            throw new HTTPException('No controller, module or routing config available for GET /'.$this->uri->getPathInfo());
        }
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
