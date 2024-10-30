<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              www.wetpaint.io
 * @since             1.0.0
 * @package           Control_Center
 *
 * @wordpress-plugin
 * Plugin Name:       SEO Control Center
 * Plugin URI:        www.wetpaint.io
 * Description:       Track your site's performance in Google, Bing, and Yahoo!
 * Version:           1.3.2
 * Author:            WetPaint
 * Author URI:        www.wetpaint.io
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       control-center
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */

// Run the class only when doing admin.
if ( is_admin() ) {
	require plugin_dir_path( __FILE__ ) . 'includes/class-control-center.php';

	Control_Center::get_instance();
}
