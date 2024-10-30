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

$keyword = $_GET['keyword'];

$api = WP_Keyword_Performance_API::get_instance();
$result = $api->get_keyword( $keyword );

$results_yet = FALSE;
$custom_chart_start = FALSE;

$created_date = time();
foreach ( $result['keyword']['serp'] as $serp ) {
	if ( strtotime( $serp['created_date'] ) < $created_date ) {
		$created_date = strtotime( $serp['created_date'] );
	}

	if ( $serp['last_rank_date'] != '0000-00-00' ) {
		$results_yet = TRUE;
	}
}

// Graph Start Date
if ( isset( $_GET['date_start'] ) ) {
	$custom_chart_start = TRUE;
	$date_start = gmdate( 'Y-m-d', strtotime( $_GET['date_start'] ) );
}
elseif ( $created_date < ( time() - strtotime("-1 month") ) ) {
	$date_start = gmdate( 'Y-m-d', strtotime( '-1 month' ) );
}
else {
	$date_start = gmdate( 'Y-m-d', $created_date );
}

// Graph End Date
$date_end = isset( $_GET['date_end'] ) ? gmdate( 'Y-m-d', strtotime( $_GET['date_end'] ) ) : gmdate( 'Y-m-d' );

// Format dates for display
$date_start_display = gmdate( 'm/d/Y', strtotime( $date_start ) );
$date_end_display = gmdate( 'm/d/Y', strtotime( $date_end ) );

$chart = $api->get_chart( $keyword, $date_start, $date_end );

// Format the api data
foreach( $chart['datasets'] as $key => $row ) {
	$engine_variable = $row['engine'] . '_locations';
	$locale = WP_Keyword_Performance::$$engine_variable;
	$region_formatted = $locale[ $row['region'] ];
	$engine_formatted = ucwords( $row['engine'] );
	$chart['datasets'][ $key ]['label'] = $engine_formatted . ' - ' . $region_formatted;
	$chart['datasets'][ $key ]['region_formatted'] = $region_formatted;
	$chart['datasets'][ $key ]['engine_formatted'] = $engine_formatted;
}

?>
<div class="wrap <?php echo $this->plugin_name; ?>_edit_page">

	<div id="icon-edit-pages" class="icon32 icon32-posts-page"></div>

	<h2><?php _e( $result['keyword']['keyword'] ); ?> <a href="<?php echo admin_url( 'admin.php?page=control-center-keyword-performance&action=add' ); ?>" class="add-new-h2">Add New</a></h2>

	<?php echo $this->notices; ?>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<form method="post" class="repeater" action="<?php echo admin_url( 'admin.php?page=control-center-keyword-performance&keyword=' . $_GET['keyword'] ); ?>" novalidate="novalidate">
				<div id="post-body-content" style="position: relative;">
					<div class="postbox">
						<div class="handlediv" title="Click to toggle"><br /></div>
						<h3 class="hndle ui-sortable-handle"><span>Keyword Requests</span></h3>
						<div class="inside">
							<table class="wp-list-table widefat fixed striped keywords">
								<thead>
									<tr>
										<th scope="col">Search Engine</th>
										<th scope="col">Region</th>
										<th scope="col">Action</th>
									</tr>
								</thead>
								<tbody data-repeater-list="request">
									<?php foreach ( $result['keyword']['serp'] as $serp ) : ?>
										<tr data-repeater-item>
											<td>
												<input type="hidden" name="location_id" class="location_id" value="<?php echo $serp['location_id']; ?>">
												<select name="engine" class="engine" disabled="disabled">
													<option<?php if ( $serp['engine'] == 'google' ) { echo ' selected="selected"'; } ?> value="google">Google</option>
													<option<?php if ( $serp['engine'] == 'bing' ) { echo ' selected="selected"'; } ?> value="bing">Bing</option>
													<option<?php if ( $serp['engine'] == 'yahoo' ) { echo ' selected="selected"'; } ?> value="yahoo">Yahoo</option>
												</select>
											</td>
											<td>
												<select<?php if ( $serp['engine'] != 'google' ) { echo ' style="display:none;"'; } ?> name="region_google" class="region_google" disabled="disabled">
													<?php
													foreach(self::$google_locations as $code => $location) {
														echo '<option value="'.$code.'"';
														if ( $code == $serp['region'] ) {
															echo ' selected="selected"';
														}
														echo '>'.$location.'</option>';
													}
													?>
												</select>
												<select<?php if ( $serp['engine'] != 'bing' ) { echo ' style="display:none;"'; } ?> name="region_bing" class="region_bing" disabled="disabled">
													<?php
													foreach(self::$bing_locations as $code => $location) {
														echo '<option value="'.$code.'"';
														if ( $code == $serp['region'] ) {
															echo ' selected="selected"';
														}
														echo '>'.$location.'</option>';
													}
													?>
												</select>
												<select<?php if ( $serp['engine'] != 'yahoo' ) { echo ' style="display:none;"'; } ?> name="region_yahoo" class="region_yahoo" disabled="disabled">
													<?php
													foreach(self::$yahoo_locations as $code => $location) {
														echo '<option value="'.$code.'"';
														if ( $code == $serp['region'] ) {
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
									<?php endforeach; ?>
								</tbody>
								<tfoot>
									<tr>
										<th colspan="3">
											<input data-repeater-create type="button" class="button" value="Add Another"/>
										</th>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>
				</div>
				<div id="postbox-container-1" class="postbox-container">
					<div id="side-sortables" class="meta-box-sortables ui-sortable" style="">
						<div id="submitdiv" class="postbox ">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class="hndle ui-sortable-handle"><span>Status</span></h3>
							<div class="inside">
								<div class="submitbox" id="submitpost">
									<div id="minor-publishing">
										<div id="misc-publishing-actions">
											<?php
											$days_this_month = date('t');
											$request_this_month = 0;

											?>
											<div class="misc-pub-section curtime misc-pub-curtime">
												<span id="timestamp">Added on: <b><?php echo date( 'M j, Y @ g:i a', $created_date ); ?></b></span>
											</div>
											<div class="misc-pub-section misc-pub-visibility" id="visibility">
												Requests used by this Keyword: <span id="post-visibility-display"><?php echo count( $result['keyword']['serp'] ); ?></span>
											</div>
											<div class="misc-pub-section misc-pub-requests-remaining">
												Keyword Requests Remaining: <strong><?php echo WP_Keyword_Performance::format_requests_remaining( $result['requests']['limit'], $result['requests']['used'] ); ?></strong><br />
												<a href="https://www.wetpaintwebtools.com/plans/" class="upgrade-link" target="_blank">Upgrade My Plan</a>
											</div>
										</div>


										<div id="major-publishing-actions">
											<div id="delete-action">
												<?php
												if ( current_user_can( "delete_posts" ) ) {
													$delete_text = __('Delete Keyword');
													$delete_url = 'admin.php?page=control-center-keyword-performance&keyword='.$keyword .'&action=delete';
													?>
													<a class="submitdelete deletion" href="<?php echo wp_nonce_url( $delete_url, 'delete_keyword'); ?>"><?php echo $delete_text; ?></a><?php
												}
												?>
											</div>
											<div id="publishing-action">
											<span class="spinner"></span>
												<input name="keyword" type="hidden" id="keyword" value="<?php echo $result['keyword']['keyword']; ?>">
												<input name="original_publish" type="hidden" id="original_publish" value="Update">
												<?php wp_nonce_field( $this->plugin_name . '_edit_keyword' ); ?>
												<input name="save" type="submit" class="button button-primary button-large" id="publish" value="Update">
											</div>
											<div class="clear"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</form>
			<div id="postbox-container-2" class="postbox-container">
				<div class="postbox rank-chart">
					<div class="handlediv" title="Click to toggle"><br></div>
					<h3 class="hndle ui-sortable-handle">
						<span>Search Rankings</span>
						<div id="date-range">
							<form method="get" action="<?php echo admin_url( 'admin.php' ); ?>" novalidate="novalidate">
								<em>Showing:</em>
								<input type="hidden" name="page" value="control-center-keyword-performance" />
								<input type="hidden" name="action" value="edit" />
								<input type="hidden" name="keyword" value="<?php echo esc_attr( $_GET['keyword'] ); ?>" />
								<input type="text" name="date_start" class="datepicker" id="date_start" value="<?php echo $date_start_display; ?>" /> &mdash;
								<input type="text" name="date_end" class="datepicker" id="date_end" value="<?php echo $date_end_display; ?>" />
								<input type="submit" class="button" value="View"  />
							</form>
						</div>
					</h3>
					<div class="inside">
					<?php if ( !empty( $chart['datasets'] ) ) : ?>
						<canvas id="rankings-chart" class="chart"></canvas>
						<div id="search-rankings-chart">
							<div style="left: 0px; top: 118px;">
								<p class="current_date">Currently Displaying: <span class="date"><?php echo $chart['labels'][0]; ?></span></p>
								<table class="table wp-list-table widefat fixed striped keyword-rankings">
									<thead>
										<tr>
											<th width="40">Line</th>
											<th width="40" class="rank">Rank</th>
											<th width="100">Engine</th>
											<th width="100">Region</th>
											<th scope="col">URL</th>
										</tr>
									</thead>
									<tbody>
										<tr id="templateRow" style="display:none">
											<td>
												<div class="legend" data-background="" style="background:#fff; width: 20px; height: 20px; border-radius: 20px;"></div>
											</td>
											<td class="rank" data-rank=""></td>
											<td data-engine=""></td>
											<td data-location=""></td>
											<td data-page=""></td>
										</tr>
									<?php
									$rownum = 0;

									foreach( $chart['datasets'] as $row ) : ?>
										<tr class="row">
											<td>
												<div class="legend" data-background="" style="background:<?php echo $row['backgroundColor']; ?>; width: 20px; height: 20px; border-radius: 20px;"></div>
											</td>
											<td class="rank" data-rank=""><?php echo $row['data'][0]; ?></td>
											<td data-engine=""><?php echo $row['engine_formatted']; ?></td>
											<td data-location=""><?php echo $row['region_formatted']; ?></td>
											<td data-page=""><?php echo $row['url']; ?></td>
										</tr>
									<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					<?php elseif ( !$results_yet ) : ?>
						<p><em><span class="dashicons dashicons-update"></span> Currently fetching rankings for this keyword. Please check back shortly.</em></p>
					<?php elseif ( !$custom_chart_start ) : ?>
						<p><em>This keyword is not ranking.</em></p>
					<?php else : ?>
						<p><em>There are no rankings for this period.</em></p>
					<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<hr class="clear">
	<script>
	jQuery(function ($) {
		var data = <?php echo json_encode($chart); ?>;

		var date_mod = Math.floor( data.labels.length / 8 );

		// Set steps to be able to show all data based on the max rank
		var maxrank = Math.max( data.stats.maxrank, 12 );
		var steps = maxrank + 2;
		var stepwidth = Math.ceil( steps / 15 );
		steps = Math.floor( steps / stepwidth );

		var options = {
			responsive: true,
			scales: {
				xAxes: [{
					display: true,
					labels: {
						userCallback: function(labelString, index) {
							return (index % date_mod === 0) ? labelString : '';
						}
					}
				}],
				yAxes: [{
					display: true,
					reverse: true,
					override: {
						start: 1,
						stepWidth: stepwidth,
						steps: steps
					}
				}]
			},
			elements: {
				line: {					// Default settings for all line elements
					tension: .4,		// Number - Bezier curve tension. Set to 0 for no bezier curves
					fill: false
				},
				point: {
					hitRadius: 40
				}
			},
			tooltips: {
				enabled: true,
				custom: function(tooltip) {
					var currently_displaying = $('.current_date .date');
					var chartdata = tooltip._data.datasets;
					var yaxis_labels = tooltip._data.labels;
					var position = yaxis_labels.indexOf(tooltip._view.title);
					var $templateRow = $('#templateRow');

					if( tooltip._view.title && currently_displaying[0].innerHTML != tooltip._view.title ) {
						currently_displaying[0].innerHTML = tooltip._view.title;
						$('.row').remove();

						$.each(chartdata, function(i, obj) {
							var $row = $templateRow.clone().removeAttr('id').addClass('row').removeAttr('style');

							$row.find('*[data-rank]').html(obj.data[position]);
							$row.find('*[data-engine]').html(obj.engine_formatted);
							$row.find('*[data-location]').html(obj.region_formatted);
							$row.find('*[data-page]').html(obj.url);
							$row.find('.legend').css( "background", obj.pointBorderColor ) ;
							$('.table').append($row);
						});
					}
				},
				backgroundColor: 'rgba( 255, 255, 255, 0.2 )',
				fontColor: '#333',
				fontSize: 12,
				titleFontSize: 14,
				titleFontColor: '#333',
				yPadding: 10,
				xPadding: 15,
				cornerRadius: 4,
				xOffset: 20,
			},
			animation: {
				duration: 0,
			}
		};

		$(window).load(function() {
			var chart = document.getElementById("rankings-chart");
			if ( chart ) {
				var ctx = document.getElementById("rankings-chart").getContext("2d");
				var myLineChart = new Chart(ctx, {
					type: 'line',
					data: data,
					options: options
				});
			}
		});
	});
	</script>
	<div id="ajax-response"></div>
</div>