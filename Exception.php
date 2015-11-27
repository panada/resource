<?php

namespace Panada\Resource;

/**
 * @author  kandar <iskandarsoesman@gmail.com>
 * @link    http://panadaframework.com/
 * @license http://www.opensource.org/licenses/bsd-license.php
 * @since   version 2.0.0
 * @package Resource
 */
class Exception extends \Exception
{
    use Controller;
    
    public function __construct($response)
    {
        $this->response = $response;
    }
    
    public function main($exception)
    {
        if (PHP_SAPI == 'cli') {
            $errorMessage = $exception->getMessage()
                ."\nFile: ".$exception->getFile()
                    .' on line '.$exception->getLine()
                ."\n\n".$exception->getTraceAsString()
                ."\n";
            // exit with an error code
            exit($errorMessage);
        }
        
        // all errors thrown by 'HTTPException' class will treat
        // as 404 error type, else will treat as 500
        if( get_class($exception) == 'Panada\Http\HTTPException' ) {
            $code = 404;
            $this->response->setStatusCode($code);
            $vars = [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ];
            
            $errorMessage = 'Error 404 Page Not Found: '.$exception->getMessage();
        }
        else {
            $code = 500;
            $this->response->setStatusCode($code);
            $vars = [
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'file' => $exception->getFile(),
                'trace' => $exception->getTraceAsString(),
                'code' => $this->viewSource($exception->getFile(), $exception->getLine()),
            ];
            
            $errorMessage = 'Error '.$exception->getMessage().' in '.$exception->getFile().' line: '.$exception->getLine();
        }
        
        // Write the error to log file
        @error_log($errorMessage);
        
        $this->uri->setConfig(['assetPath' => '/']);
        
        $this->response->setBody(
            $this->output('errors/'.$code, $vars)
        );
        
        return $this->response;
    }

    public function errorHandler($errno, $message, $file, $line)
    {
        throw new \ErrorException($message, 0, 1, $file, $line); 
    }
    
    private function viewSource($file, $line)
    {
        $fileString     = file_get_contents($file);
        $arrLine        = explode("\n", $fileString);
        $totalLine      = count($arrLine);
        $getLine        = array_combine(range(1, $totalLine), array_values($arrLine));
        $startIterate   = $line - 5;
        $endIterate     = $line + 5;
        
        if($startIterate < 0) {
            $startIterate  = 1;
        }
        
        if($endIterate > $totalLine) {
            $endIterate = $totalLine;
        }
        
        for($i = $startIterate; $i <= $endIterate; $i++){
            
            $html = '<span style="margin-right:10px;background:#CFCFCF;">'.htmlentities($i).'</span>';
            
            if($line == $i ) {
                $html .= '<span style="color:#DD0000">'.htmlentities($getLine[$i]) . "</span>\n";
            }
            else {
                $html .= htmlentities($getLine[$i]) . "\n";
            }
            
            $code[] = $html;
        }
        
        return $code;
    }
}