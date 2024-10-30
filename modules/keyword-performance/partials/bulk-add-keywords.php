<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       wetpaint.io
 * @since      1.0.0
 *
 * @package    Keyword_Performance
 * @subpackage Keyword_Performance/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$api = WP_Keyword_Performance_API::get_instance();
$requests = $api->get_account_limits();
$keyword_requests_remaining = ( '' != $requests['requests']['limit'] && '' != $requests['requests']['used'] ) ? WP_Keyword_Performance::format_requests_remaining( $requests['requests']['limit'], $requests['requests']['used'] ) : '';
?>
<div class="wrap <?php echo $this->plugin_name; ?>_table_page">

	<div id="icon-edit-pages" class="icon32 icon32-posts-page"></div>

	<h2><?php _e( 'Bulk Add Keywords' ); ?></h2>

	<form method="post" action="<?php echo admin_url( 'admin.php?page=control-center-keyword-performance' ); ?>" class="repeater" novalidate="novalidate">

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content" style="position: relative;">

				<div class="postbox ">
					<div class="handlediv" title="Click to toggle"><br></div>
					<h3 class="hndle ui-sortable-handle"><span>Keywords</span></h3>
					<div class="inside">
						<p><em>Add keywords below, one per line.</em></p>
						<textarea name="keyword" spellcheck="true" autocomplete="off"></textarea>
					</div>
				</div>

				<div class="postbox">
					<div class="handlediv" title="Click to toggle"><br></div>
					<h3 class="hndle ui-sortable-handle"><span>Keyword Requests</span></h3>
					<div class="inside">
						<p><em>Setup keyword requests. These will apply to every keyword entered above.</em></p>
						<table class="wp-list-table widefat fixed striped keywords">
							<thead>
								<tr>
									<th scope="col">Search Engine</th>
									<th scope="col">Region</th>
									<th scope="col">Action</th>
								</tr>
							</thead>
							<tbody data-repeater-list="request">
									<tr data-repeater-item>
										<td>
											<select name="engine" class="engine">
												<option value="google" selected="selected">Google</option>
												<option value="bing">Bing</option>
												<option value="yahoo">Yahoo</option>
											</select>
										</td>
										<td>
											<select name="region_google" class="region_google">
												<?php
												foreach(self::$google_locations as $code => $location) {
													echo '<option value="'.$code.'"';
													if ( $code == 'en-us' ) {
														echo ' selected="selected"';
													}
													echo '>'.$location.'</option>';
												}
												?>
											</select>
											<select style="display:none;" name="region_bing" class="region_bing">
												<?php
												foreach(self::$bing_locations as $code => $location) {
													echo '<option value="'.$code.'"';
													if ( $code == 'en-us' ) {
														echo ' selected="selected"';
													}
													echo '>'.$location.'</option>';
												}
												?>
											</select>
											<select style="display:none;" name="region_yahoo" class="region_yahoo">
												<?php
												foreach(self::$yahoo_locations as $code => $location) {
													echo '<option value="'.$code.'"';
													if ( $code == 'en-us' ) {
														echo ' selected="selected"';
													}
													echo '>'.$location.'</option>';
												}
												?>
											</select>
										</td>
										<td>
											<a href="#" data-repeater-delete>Remove</a>
										</td>
									</tr>
							</tbody>
							<tfoot>
								<tr>
									<th colspan="3">
										<input data-repeater-create type="button" class="button" value="Add Another"/>
									</th>
								</tr>
							</tfoot>
						</table>

						<?php
						wp_nonce_field( $this->plugin_name . '_bulk_add_keyword' );
						?>
					</div>
				</div>
			</div>

			<div id="postbox-container-1" class="postbox-container">
				<div id="side-sortables" class="meta-box-sortables ui-sortable" style="">
					<div id="submitdiv" class="postbox ">
						<div class="handlediv" title="Click to toggle">
							<br>
						</div>
						<h3 class="hndle ui-sortable-handle"><span>Status</span></h3>
						<div class="inside">
							<div class="submitbox" id="submitpost">
								<div id="minor-publishing">
									<div id="misc-publishing-actions">
										<?php if ( ! empty( $requests['requests']['limit'] ) ) : ?>
											<div class="misc-pub-section misc-pub-requests-remaining">
												Keyword Requests Remaining: <strong><?php echo $keyword_requests_remaining; ?></strong><br />
												<a href="https://www.wetpaintwebtools.com/plans/" class="upgrade-link" target="_blank">Upgrade My Plan</a>
											</div>
										<?php endif; ?>
									</div>
									<div id="major-publishing-actions">
										<div id="publishing-action">
										<span class="spinner"></span>
											<?php if ( $keyword_requests_remaining != 0 ) : ?>
												<input name="save" type="submit" class="button button-primary button-large" id="publish" value="Save">
											<?php else: ?>
												<?php add_thickbox(); ?>
												<div id="upgrade-modal" style="display:none;">
													<div class="upgrade-wrapper">
														<div class="upgrade-content">
															<div class="upgrade-text-top">Uh-oh, it looks like you're out of keywords!</div>
															<div class="upgrade-text-bottom">Upgrade your plan to track more keywords and grow site traffic</div>
															<a class="button" href="https://www.wetpaintwebtools.com/plans/?utm_source=control-center-plugin-ad&utm_medium=in-app-advertisement&utm_content=bulk-add-keywords-uh-oh&utm_campaign=Upsell%20Ad" target="_blank">Upgrade Your Plan</a>
														</div>
													</div>
												</div>
												<a href="#TB_inline?width=600&height=300&inlineId=upgrade-modal" type="submit" class="thickbox button button-primary button-large" id="publish" value="Save">Save</a>
											<?php endif; ?>
										</div>
										<div class="clear"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	</form>

	<div id="ajax-response"></div>
	<br class="clear" />
</div>