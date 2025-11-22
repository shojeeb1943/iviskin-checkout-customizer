<?php
namespace Iviskin\CheckoutCustomizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Template_Loader {

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 10, 3 );
	}

	public function locate_template( $template, $template_name, $template_path ) {
		global $woocommerce;

		$_template = $template;

		if ( ! $template_path ) {
			$template_path = $woocommerce->template_url;
		}

		$plugin_path  = ICC_PLUGIN_DIR . 'templates/woocommerce/';

		// Look within passed path within the theme - this is priority
		$theme_path = get_stylesheet_directory() . '/' . $template_path . $template_name;

		// If the file doesn't exist in the theme, check the plugin
		if ( ! file_exists( $theme_path ) ) {
			$plugin_file = $plugin_path . $template_name;
			if ( file_exists( $plugin_file ) ) {
				$_template = $plugin_file;
			}
		}

		// Force plugin template for specific files if we want to override theme (optional)
		// For now, we follow standard hierarchy: Child Theme > Plugin > Parent Theme
		// But since the goal is to replace the child theme logic, we might want to force it
		// or simply assume the user will remove the child theme overrides.
		// Let's stick to standard hierarchy: Plugin provides fallback if not in theme.
		// However, if the user wants the plugin to take over, they should remove the files from the child theme.
		
		// To ensure the plugin works even if the child theme has files (during migration),
		// we can check if the file exists in the plugin and return it.
		// But standard practice is Theme > Plugin.
		// Let's stick to standard practice. The user should delete the child theme files.
		
		return $_template;
	}
}
