<?php
/**
 * Plugin Name: Iviskin Checkout Customizer
 * Plugin URI: https://iviskin.no
 * Description: Customizes the WooCommerce checkout experience for Iviskin (Flatsome dependent).
 * Version: 2.8
 * Author: Softvila
 * Author URI: https://softvila.com
 * Text Domain: iviskin-checkout-customizer
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package Iviskin\CheckoutCustomizer
 */

namespace Iviskin\CheckoutCustomizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'ICC_VERSION', '2.8' );
define( 'ICC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ICC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Autoloader for classes.
spl_autoload_register( function ( $class ) {
	$prefix = 'Iviskin\\CheckoutCustomizer\\';
	$base_dir = ICC_PLUGIN_DIR . 'inc/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	$file = $base_dir . 'class-' . str_replace( '_', '-', strtolower( $relative_class ) ) . '.php';

	if ( file_exists( $file ) ) {
		require $file;
	}
} );

// Initialize the plugin.
function init() {
	Core::instance();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
