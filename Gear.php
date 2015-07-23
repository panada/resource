<?php

namespace Panada\Resources;

use Panada;

class Gear
{
    private $body;
    
    public function __construct()
    {
        $exception = new Panada\Resources\Exception;
        
        set_exception_handler([$exception, 'main']);
        set_error_handler([$exception, 'errorHandler'], E_ALL);
        
        $this->response     = Panada\Resources\Response::getInstance();
        $this->uri          = Panada\Request\Uri::getInstance();
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
            
            Panada\Resources\Config::routes();
            
            $route = Panada\Router\Routes::getInstance()
                ->parse(
                    $_SERVER['REQUEST_METHOD'],
                    '/'.$this->uri->getPathInfo()
                );
            
            if(! $route) {
                if( get_class($e) == 'ErrorException' ) {
                    throw $e;
                }
                
                throw new \Exception('No controller or routing config available for GET /'.$this->uri->getPathInfo());
            }
            
            try{
                $instance   = new $route['controller'];
                $action     = $route['action'];
                $request    = $route['args'];
            }
            catch(\Exception $e) {
                throw new \Exception('Routing for GET /'.$this->uri->getController().' is available but no controller or method can handle it. Please check your routing config.');
            }
        }

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