<?php
namespace BlueFission\Data;

use BlueFission\Behavioral\Configurable;

class Data extends Configurable implements IData
{
	public function read() { }
	public function write() { }
	public function delete() { }
	public function contents() { }
		
	public function data() 
	{
		return $this->_data;
	}
	
	public function registerGlobals( string $source = null )
	{
		$source = strtolower($source);
		switch( $source )
		{
			case 'post':
				$vars = $_POST;
			break;
			case 'get':
				$vars = $_GET;
			break;
			case 'session':
				$vars = $_SESSION;
			break;
			case 'cookie':
			case 'cookies':
				$vars = $_COOKIE;
			break;
			default:
			case 'globals':
				$vars = $GLOBALS;
			break;
			case 'request':
				$vars = $_REQUEST;
			break;
		}
		
		$this->assign($vars);
	}
}