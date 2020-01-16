<?php
/*
Plugin Name: Connect EmailOctopus and Gravity Forms
Plugin URI: http://wordpress.org/plugins/connect-eo-gf/
Description: EmailOctopus add-on for Gravity Forms
Author: Ronald Huereca
Version: 2.0.0
Requires at least: 4.9
Author URI: https://mediaron.com
Contributors: ronalfy
Text Domain: connect-eo-gf
Domain Path: /languages
*/

class EmailOctopus_Gravity_Forms {

	/**
	 * Holds the class instance.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private static $instance = null;

	/**
	 * Retrieve a class instance.
	 *
	 * @since 1.0.0
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	} //end get_instance

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		load_plugin_textdomain( 'connect-eo-gf', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		spl_autoload_register( array( $this, 'loader' ) );

		add_action( 'gform_loaded', array( $this, 'gforms_loaded' ) );
	}

	/**
	 * Check for the minimum supported PHP version.
	 *
	 * @since 1.0.0
	 *
	 * @return bool true if meets minimum version, false if not
	 */
	public static function check_php_version() {
		if( ! version_compare( '5.6', PHP_VERSION, '<=' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Check the plugin to make sure it meets the minimum requirements.
	 *
	 * @since 1.0.0
	 */
	public static function check_plugin() {
		if( ! self::check_php_version() ) {
			deactivate_plugins( GFGAET::get_plugin_basename() );
			exit( sprintf( esc_html__( 'EmailOctopus for Gravity Forms requires PHP version 5.6 and up. You are currently running PHP version %s.', 'connect-eo-gf' ), esc_html( PHP_VERSION ) ) );
		}
	}

	/**
	 * Retrieve the plugin basename.
	 *
	 * @since 1.0.0
	 *
	 * @return string plugin basename
	 */
	public static function get_plugin_basename() {
		return plugin_basename( __FILE__ );
	}

	/**
	 * Return the absolute path to an asset.
	 *
	 * @since 1.0.0
	 *
	 * @param string @path Relative path to the asset.
	 *
	 * return string Absolute path to the relative asset.
	 */
	public static function get_plugin_dir( $path = '' ) {
		$dir = rtrim( plugin_dir_path(__FILE__), '/' );
		if ( !empty( $path ) && is_string( $path) )
			$dir .= '/' . ltrim( $path, '/' );
		return $dir;
	}

	/**
	 * Initialize Gravity Forms related add-ons.
	 *
	 * @since 1.0.0
	 */
	public function gforms_loaded() {
		if ( ! self::check_php_version() ) return;
		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		// Initialize settings screen and feeds
		GFAddOn::register( 'EmailOctopus\GF\API\EOGF_API' );
		GFAddOn::register( 'EmailOctopus\GF\Feeds\EOGF_Feeds' );
	}

	/**
	 * Autoload class files.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The class name
	 */
	public function loader( $class_name ) {
		$parts = explode( '\\', $class_name );
		$class_name = end( $parts );
		if ( class_exists( $class_name, false ) || false === strpos( $class_name, 'EOGF' ) ) {
			return;
		}
		$file = self::get_plugin_dir( "includes/{$class_name}.php" );
		if ( file_exists( $file ) ) {
			include_once( $file );
		}
	}
}

register_activation_hook( __FILE__, array( 'EmailOctopus_Gravity_Forms', 'check_plugin' ) );
EmailOctopus_Gravity_Forms::get_instance();