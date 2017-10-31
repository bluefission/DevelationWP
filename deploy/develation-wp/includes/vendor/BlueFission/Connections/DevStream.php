<?php
use \BlueFission;
@include_once('Loader.php');
$loader = BlueFission\Loader::instance();
$loader->load('com.bluefission.develation.functions.common');
$loader->load('com.bluefission.develation.interfaces.IDevConfigurable');
$loader->load('com.bluefission.develation.connections.DevConnection');

class DevStream extends DevConnection implements IDevConfigurable
{
	protected $_config = array( 'target'=>'',
		'wrapper'=>'http',
		'method'=>'GET',
		'header'=>"Content-type: application/x-www-form-urlencoded\r\n",
	);
	
	public function __construct( $config = null )
	{
		parent::__construct();
	}
	
	public function open() 
	{
		$target = $this->config('target') ? $this->config('target') : dev_domain();
		$method = $this->config('method');
		$header = $this->config('header'); 
		$wrapper = $this->config('wrapper');
		
		if ( dev_url_exists($target) )
		{
			$options = array(
				$wrapper => array(
					'header'	=>	$header,
					'method'	=>	$method,
				),
			);
			$this->_connection = stream_context_create($options);
			$status = $this->_connection ? self::STATUS_CONNECTED : self::STATUS_NOTCONNECTED;
		}
		else
		{
			$status = self::STATUS_NOTCONNECTED;
		}
		$this->status($status);
	}
	
	public function query ( $query = null )
	{ 
		
		$status = self::STATUS_NOTCONNECTED;
		$context = $this->_connection;
		$wrapper = $this->config('wrapper');
		
		if ($context)
		{
			if (dev_not_null($query))
			{
				if (dev_is_assoc($query))
				{
					$this->_data = $query; 
				}
				else if (is_string($query))
				{
					$data = urlencode($query);	
					stream_context_set_option ( $context, $wrapper, 'content', $data );			
					$this->_result = file_get_contents($target, false, $context);
					
					$this->status( $this->_result !== false ? self::STATUS_SUCCESS : self::STATUS_FAILED );
					return true;
				}
			}
			$data = dev_http_query( $this->_data );	
	
			stream_context_set_option ( $context, $wrapper, 'content', $data );			
	
			$this->_result = file_get_contents($target, false, $context);
			
			if ($this->_result !== false)
				$status = self::STATUS_SUCCESS;
			
		}
		$this->status($status);
	}
}