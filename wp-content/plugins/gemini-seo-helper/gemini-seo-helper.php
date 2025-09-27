<?php
/**
 * Plugin Name: Gemini SEO Helper
 * Plugin URI:  https://example.com/gemini-seo-helper
 * Description: A WordPress plugin to assist with SEO using Gemini AI.
 * Version:     1.0.0
 * Author:      Manus AI
 * Author URI:  https://manus.im
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gemini-seo-helper
 * Domain Path: /languages
 *
 * @package Gemini_SEO_Helper
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-gemini-seo-helper-activator.php
 */
function activate_gemini_seo_helper() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gemini-seo-helper-activator.php';
	Gemini_SEO_Helper_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-gemini-seo-helper-deactivator.php
 */
function deactivate_gemini_seo_helper() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gemini-seo-helper-deactivator.php';
	Gemini_SEO_Helper_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_gemini_seo_helper' );
register_deactivation_hook( __FILE__, 'deactivate_gemini_seo_helper' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-gemini-seo-helper.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks, the plugin
 * will not be activated until some of the WordPress hooks are triggered.
 */
function run_gemini_seo_helper() {

	$plugin = new Gemini_SEO_Helper();
	$plugin->run();

}
run_gemini_seo_helper();

