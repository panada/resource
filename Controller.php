<?php

namespace Panada\Resource;

use Panada;
use Panada\Resource\Loader;

/**
 * Handler for controller process.
 *
 * @author  Iskandar Soesman <k4ndar@yahoo.com>
 * @link    http://panadaframework.com/
 * @license http://www.opensource.org/licenses/bsd-license.php
 * @since   version 1.0.0
 * @package Resource
 */
trait Controller
{    
    private $childNamespace;
	private $viewCache;
    private $viewFile;
    private $configMain;
    
    public $config = [];
    
    public function __construct()
    {    
        $this->getChildInfo();
    }
	
	public function __get($class)
    {    
        $classNamespace = [
            'response' => 'Panada\Resource\Response',
			'uri' => 'Panada\Request\Uri'
        ];
        
		try {
			return call_user_func($classNamespace[$class].'::getInstance');
		}
		catch(\Exception $e) {
			try {
				$this->$class;
			}
			catch(\Exception $e) {
				throw new \ErrorException($e->getMessage(), 0, 1, $e->getTrace()[1]['file'], $e->getTrace()[1]['line']);
			}
		}
    }
	
	private function getChildInfo()
	{
		$child = get_class($this);
        
        $this->childClass = [
			'namespaceArray' => explode( '\\', $child),
			'namespaceString' => $child
		];
	}
    
    public function output($panadaViewfile, $data = [], $fromMainView = false)
    {
		$this->getChildInfo();
		
		$app = Loader::$maps['Controller'];
		
        $panadaFilePath = $app.'view/'.$panadaViewfile;
		
		if( $this->childClass['namespaceArray'][0] == 'Module' && !$fromMainView ) {
            $panadaFilePath = Loader::$maps['Controller'].$this->childClass['namespaceArray'][0].'/'.$this->childClass['namespaceArray'][1].'/view/'.$panadaViewfile;
        }
		
		$this->viewFile = $panadaFilePath;
        
        if( ! empty($data) ){
            $this->viewCache = [
                'data' => $data,
                'prefix' => $this->childClass['namespaceString'],
            ];
        }
        
        // We don't need this variables anymore.
        unset($panadaViewFile, $data, $panadaFilePath);
        
        if(! empty($this->viewCache) && $this->viewCache['prefix'] == $this->childClass['namespaceString'] ) {
            extract( $this->viewCache['data'], EXTR_SKIP );
        }
        
		try{
			ob_start();
			include $this->viewFile.'.php';
			return ob_get_clean();
		}
		catch(\Exception $e) {
			throw $e;
		}
    }
}
