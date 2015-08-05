<?php

namespace Panada\Resource;

/**
 * @author kandar <iskandarsoesman@gmail.com>
 */
class Exception extends \Exception
{
    public function __construct()
    {
        $this->response = \Panada\Resource\Response::getInstance();
    }
    
    public function main($exception)
    {
        // all errors thrown by 'Exception' class will treat
        // as 404 error type, else will treat as 500
        if( get_class($exception) == 'Exception' ) {
            $code = 404;
            $this->response->setStatus('HTTP/1.0 404 Not Found', $code);
            $vars = [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ];
        }
        else {
            $code = 500;
            $this->response->setStatus('HTTP/1.1 500 Internal Server Error', $code);
            $vars = [
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
                'file' => $exception->getFile(),
                'trace' => $exception->getTraceAsString(),
                'code' => $this->viewSource($exception->getFile(), $exception->getLine()),
            ];
        }
        
        $this->response->setBody(
            (new Controller)->output('errors/'.$code, $vars)
        );
        
        $this->response->output();
        exit;
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