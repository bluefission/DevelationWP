<?php

if ( !class_exists("BlueFission_Plugin_Init") ) {
	require_once( plugin_dir_path( __FILE__ ) . 'includes/class-bluefission-plugin-init.php');
}

class DevElation_WP_Init extends BlueFission_Plugin_Init {
    protected $plugin_name = 'Develation WP'; // Change this, then magic

    public function get_plugin_slug() {
		return $this->plugin_meta['slug'];
	}

	protected function __construct() {
		parent::__construct();

		define ( 'DEVELATION_DIR', plugin_dir_path( __FILE__ ).'includes/vendor/' );

		spl_autoload_register( function ( $className ) {
			$className = str_replace(
				array('/', '\\'),
				array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR),
				$className);

			if ( file_exists ( DEVELATION_DIR . $className . ".php" ) )
	    		include_once ( DEVELATION_DIR . $className . ".php" );
		});
	}
}
