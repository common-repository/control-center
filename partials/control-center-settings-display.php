<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       www.wetpaint.io
 * @since      1.0.0
 *
 * @package    Control_Center
 * @subpackage Control_Center/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<div class="wrap">

	<div id="icon-edit-pages" class="icon32 icon32-posts-page"></div>

	<h2><?php _e( 'SEO Keywords Settings' ); ?></h2>

		<?php settings_errors(); ?>

		<form action='options.php' method='post'>

			<?php do_action('control_center_settings'); ?>

		</form>

	<div id="ajax-response"></div>
	<br class="clear" />
</div>
