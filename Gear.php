<?php

namespace Panada\Resource;

use Panada;

class Gear
{
    private $body;
    
    public function __construct(Panada\Request\Uri $uri, Panada\Resource\Response $response)
    {
        $this->uri          = $uri;
        $this->response     = $response;
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
        Panada\Resource\Config::routes();
            
        $route = Panada\Router\Routes::getInstance()
            ->parse(
                $this->uri->getRequestMethod(),
                '/'.$this->uri->getPathInfo()
            );
        
        if($route) {
            try{
                $this->run(new $route['controller'], $route['action'], $route['args']);
            }
            catch(\Exception $e) {
                throw new \Exception('Routing for GET /'.$this->uri->getController().' is available but no controller or method can handle it. Please check your routing config.');
            }
        }
        else {
            throw new \Exception('No controller, module or routing config available for GET /'.$this->uri->getPathInfo());
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
            
            if ( substr($e->getMessage(), 0, 4) == 'call_user_func' ) {
                throw new \Exception('No action or routing config available for GET /'.$this->uri->getPathInfo());
            }
            
            throw $e;
        }
    }
    
    public function output()
    {
        $this->response->output();
    }
}
