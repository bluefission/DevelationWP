<?php
/**
 * @package DevElation_WP
 * @version 1.2.0
 */
/*
Plugin Name: BlueFission DevElation
Plugin URI: http://bluefission.com/wordpress-plugins
Description: Include the BlueFission DevElation class library
Author: Devon Scott
Version: 1.2.0
Author URI: http://bluefission.com
*/

function develation_wp_init()
{
    require_once(plugin_dir_path(__FILE__) . 'class-develation-wp-init.php');
    DevElation_WP_Init::get_instance();
    $init = DevElation_WP_Init::get_instance();
    do_action($init->get_plugin_slug() .'_instantiated');
}
add_action('plugins_loaded', 'develation_wp_init');
