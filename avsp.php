<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/Anny0007/AV-summarize-posts
 * @since             1.0.0
 * @package           Avsp
 *
 * @wordpress-plugin
 * Plugin Name:       AV summarize posts (text to speech)
 * Plugin URI:        https://wordpress.org
 * Description:       This plugin automatically reads your post content, generates a concise AI-powered summary, and transforms it into a high-quality audio narration.
 * Version:           1.0.0
 * Author:            Ankit Vishwakarma
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       avsp
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'AVSP_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-avsp-activator.php
 */
function activate_avsp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-avsp-activator.php';
	Avsp_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-avsp-deactivator.php
 */
function deactivate_avsp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-avsp-deactivator.php';
	Avsp_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_avsp' );
register_deactivation_hook( __FILE__, 'deactivate_avsp' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-avsp.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_avsp() {

	$plugin = new Avsp();
	$plugin->run();

}
run_avsp();
