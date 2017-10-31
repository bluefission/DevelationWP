<?php
use \BlueFission;
@include_once('Loader.php');
$loader = BlueFission\Loader::instance();
$loader->load('com.bluefission.develation.functions.common');
$loader->load('com.bluefission.develation.interfaces.IDevConfigurable');
$loader->load('com.bluefission.develation.connections.DevConnection');

class DevCurl extends DevConnection implements IDevConfigurable
{
	protected $_result;

	protected $_config = array( 'target'=>'',
		'method'=>'',
		'refresh'=>false,		
	);
	
	public function __construct( $config = '' )
	{
		parent::__construct();
		if (is_array($config))
			$this->config($config);
	}
	
	public function open()
	{
		$status = '';
		$target = $this->config('target') ? $this->config('target') : dev_domain();
		$refresh = (bool)$this->config('refresh');
		
		if ( dev_url_exists($target) )
		{				
			$data = $this->_data;
			
			//open connection
			$this->_connection = curl_init();
			
			curl_setopt($this->_connection, CURLOPT_URL, $url);
			curl_setopt($this->_connection, CURLOPT_COOKIESESSION, $refresh);
			
			$status = $this->_connection ? self::STATUS_CONNECTED : self::STATUS_NOTCONNECTED;
		}
		else
		{
			$status = self::STATUS_NOTCONNECTED;		
		}
		$this->status($status);
	}
	
	public function close ()
	{
		curl_close($this->_connection);
		
		// clean up
		parent::close();
	}
	
	public function query ( $query = null )
	{ 
		$curl = $this->_connection;
		$method = strtolower($this->config('method'));
		
		if ($curl)
		{
			if (dev_not_null($query))
			{
				if (dev_is_assoc($query))
				{
					//$this->_data = $query; 
					$this->assign($query);
				}
			}
			$data = $this->_data;
			//set the url, number of POST vars, POST data
			curl_setopt($curl,CURLOPT_POST, count($data));
			curl_setopt($curl,CURLOPT_POSTFIELDS, dev_http_query($data));
			curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
			
			//execute post
			$status = ($this->_result = curl_exec($ch)) ? self::STATUS_SUCCESS : self::STATUS_FAILED;
		}
		else
		{
			$status = self::STATUS_NOTCONNECTED;
		}	
		$this->status($status);
	}
}