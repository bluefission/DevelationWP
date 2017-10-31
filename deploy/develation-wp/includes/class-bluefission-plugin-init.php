<?php
/**
 * @package BlueFission Plugin Init
 * @version 1.0
 */
/*
Plugin Name: BlueFission Plugin Base
Plugin URI: http://bluefission.com/wordpress-plugins
Description: Create custom plugin with update api calls
Author: Devon Scott
Version: 1.0
Author URI: http://bluefission.com
*/

class BlueFission_Plugin_Init {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	protected $plugin_name = 'BlueFission Plugin';

	protected $api_url = 'http://code.bluefission.com/wp/updates';

	protected $plugin_location = __FILE__;

	protected $plugin_ajax = false;

	protected $plugin_meta;

	protected static $instances = array();

	protected function __construct() {

		$this->called_class = get_called_class();

		$this->plugin_meta = array();

		$this->generate_names();

		//$this->plugin_meta['location'] = plugin_dir_path( $this->plugin_location );
		$this->plugin_meta['location'] = $this->plugin_location;

		$this->load_controller();
		$this->load_admin();

		// Take over the update check
		add_filter('pre_set_site_transient_update_plugins', array( $this, 'check_for_plugin_update' ) );

		// Take over the Plugin info screen
		add_filter('plugins_api', array( $this, 'plugin_api_call' ), 10, 3);

		// TEMP: Enable update check on every request. Normally you don't need this! This is for testing only!
		// NOTE: The 
		//	if (empty($checked_data->checked))
		//		return $checked_data; 
		// lines will need to be commented in the check_for_plugin_update function as well.

		//set_site_transient('update_plugins', null);

		// TEMP: Show which variables are being requested when query plugin API
		//add_filter('plugins_api_result', array( $this, 'api_result' ) , 10, 3);
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		$called_class = get_called_class();
		// If the single instance hasn't been set, set it now.
		if ( !isset(static::$instances[$called_class]) ) {
			static::$instances[$called_class] = new $called_class();
		}

		return static::$instances[$called_class];
	}

	protected function generate_names() {
		$plugin_name = $this->plugin_name;

		$this->plugin_meta['name'] = $plugin_name;
		$this->plugin_meta['slug'] = strtolower( str_replace( " ", "-", $plugin_name ) );
		$this->plugin_meta['options'] = $this->plugin_meta['slug'].'-options';
		$this->plugin_meta['class'] = str_replace( " ", "_", ucwords( $plugin_name ) );
		$this->plugin_meta['class-file'] = 'class-'.$this->plugin_meta['slug'].'.php';
		$this->plugin_meta['admin-class'] = $this->plugin_meta['class'] .'_Admin';
		$this->plugin_meta['admin-class-file'] = 'class-'.$this->plugin_meta['slug'].'-admin.php';
	}

	protected function load_controller() {
		if ( !class_exists( $this->plugin_meta['class'] ) ) {
			if ( file_exists( plugin_dir_path( $this->plugin_location ) . 'public/'.$this->plugin_meta['class-file'] ) ) {
				require_once( plugin_dir_path( $this->plugin_location ) . 'public/'.$this->plugin_meta['class-file'] );
			}
		}

		if ( class_exists( $this->plugin_meta['class'] ) ) {
			register_activation_hook( $this->plugin_location, array( $this->plugin_meta['class'], 'activate' ) );
			register_deactivation_hook( $this->plugin_location, array( $this->plugin_meta['class'], 'deactivate' ) );
		}

		add_action( 'plugins_loaded', array( $this, 'init_controller' ) );
	}

	public function init_controller() {
		if ( class_exists( $this->plugin_meta['class'] ) ) {
			call_user_func( array( $this->plugin_meta['class'], 'set_init' ), $this );
			call_user_func( array( $this->plugin_meta['class'], 'get_instance' ) );
		}
	}

	protected function load_admin() {
		if ( is_admin() && ( !$this->plugin_ajax || ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) ) ) {

			if ( !class_exists( $this->plugin_meta['admin-class'] ) ) {
				if ( file_exists( plugin_dir_path( $this->plugin_location ) . 'admin/'.$this->plugin_meta['admin-class-file'] ) ) {
					require_once( plugin_dir_path( $this->plugin_location ) . 'admin/'.$this->plugin_meta['admin-class-file'] );
				}
			}

			add_action( 'plugins_loaded', array( $this, 'init_admin' ) );
		}
	}

	public function init_admin() {
		if ( class_exists( $this->plugin_meta['admin-class'] ) ) {
			call_user_func( array( $this->plugin_meta['admin-class'], 'set_init' ), $this );
			call_user_func( array( $this->plugin_meta['admin-class'], 'get_instance' ) );
		}
	}

	public function plugin_info($info = null) {
		if ( array_key_exists($info, $this->plugin_meta) ) {
			return $this->plugin_meta[$info];
		} elseif ( $info == null ) {
			return $this->plugin_meta;
		}
	}

	public function require_version( $version ) {
		$difference = version_compare(self::VERSION, $version);
		if ( -1 == $difference ) {
			add_action( 'admin_notices', 'upgrade_core_version' );
		}
	}

	public function upgrade_core_version() {
	    ?>
	    <div class="warning">
	        <p><?php _e( 'This plugin requires an update to the BlueFission Plugin core.', $this->plugin_meta['slug'] ); ?></p>
	    </div>
	    <?php
	}

	public function api_result($res, $action, $args) {
		print_r($res);
		return $res;
	}

	/**
	 *
	 *
	 */
	private function get_remote_content() {
		global $wp_version;
		$api_url = $this->remote_url;

		$args = array(
			
		);
		$request_string = array(
			'body' => array(
				'action' => 'get_content', 
				'request' => serialize($args),
				'api-key' => md5(get_bloginfo('url'))
			),
			'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
		);
		
		// Start checking for an update

		$raw_response = wp_remote_post($api_url, $request_string);
		
		if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
			$response = $raw_response['body'];

		if (false != $response) {
			$data = json_decode($response);
			$this->remote_data['message'] = $data->message;
			$this->remote_data['message2'] = $data->message2;
		}

	}

	/**
	 * NOTE:  Filters are points of execution in which WordPress modifies data
	 *        before saving it or sending it to the browser.
	 *
	 *        Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.3.0
	 */
	public function check_for_plugin_update($checked_data) {
		global $wp_version;
		$plugin_slug = $this->plugin_info('slug');
		$api_url = $this->api_url;
		
		//Comment out these two lines during testing.
		if (empty($checked_data->checked))
			return $checked_data;
		
		$args = array(
			'slug' => $plugin_slug,
			'version' => isset( $plugin_info->checked[$plugin_slug .'/'. $plugin_slug .'.php'] ) ? $plugin_info->checked[$plugin_slug .'/'. $plugin_slug .'.php'] : '1.0.0',
		);
		$request_string = array(
				'body' => array(
					'action' => 'basic_check', 
					'request' => serialize($args),
					'api-key' => md5(get_bloginfo('url'))
				),
				'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
			);
		
		// Start checking for an update

		$raw_response = wp_remote_post($api_url, $request_string);
		
		if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
			$response = unserialize($raw_response['body']);
		
		if (is_object($response) && !empty($response)) // Feed the update data into WP updater
			$checked_data->response[$plugin_slug .'/'. $plugin_slug .'.php'] = $response;
		
		return $checked_data;
	}

	public function plugin_api_call($def, $action, $args) {
		global $wp_version;
		$plugin_slug = $this->plugin_info('slug');
		$api_url = $this->api_url;
		
		if (!isset($args->slug) || ($args->slug != $plugin_slug))
			return false;
		
		// Get the current version
		$plugin_info = get_site_transient('update_plugins');
		$current_version = isset( $plugin_info->checked[$plugin_slug .'/'. $plugin_slug .'.php'] ) ? $plugin_info->checked[$plugin_slug .'/'. $plugin_slug .'.php'] : '1.0.0';
		$args->version = $current_version;
		
		$request_string = array(
				'body' => array(
					'action' => $action, 
					'request' => serialize($args),
					'api-key' => md5(get_bloginfo('url'))
				),
				'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
			);
		
		$request = wp_remote_post($api_url, $request_string);
		
		if (is_wp_error($request)) {
			$res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>'), $request->get_error_message());
		} else {
			$res = unserialize($request['body']);
			
			if ($res === false)
				$res = new WP_Error('plugins_api_failed', __('An unknown error occurred'), $request['body']);
		}
		
		return $res;
	}
}