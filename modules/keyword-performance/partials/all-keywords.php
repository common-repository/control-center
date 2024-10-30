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

$option = get_option( 'control_center_keyword_performance' );

?>
<div class="wrap keyword-performance-page">

	<div id="icon-edit-pages" class="icon32 icon32-posts-page"></div>

	<h2>
		<?php _e( 'SEO Keywords' ); ?>
		<?php if ( !empty( $option['api_key'] ) ) : ?>
		<a href="<?php echo admin_url( 'admin.php?page=control-center-keyword-performance&action=add' ); ?>" title="Add New Keyword" class="add-new-h2">Add New</a>
		<a href="<?php echo admin_url( 'admin.php?page=control-center-keyword-performance&action=bulk-add' ); ?>" title="Add New Keywords In Bulk" class="add-new-h2">Bulk Add</a>
		<a href="<?php echo admin_url( 'admin.php?page=control-center-keyword-performance&action=export' ); ?>" title="Export to CSV" class="add-new-h2">Export to CSV</a>
		<?php endif; ?>
		<?php if ( ! empty( $_REQUEST['s'] ) )
			printf( ' <span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', esc_attr( $_REQUEST['s'] ) ); ?>
	</h2>

	<?php echo $this->notices; ?>

	<?php if ( $rank_table->raw_items && count( $rank_table->raw_items ) > 0 ) : ?>
		<canvas id="rankings-chart" class="chart" style="display:none;"></canvas>
	<?php endif; ?>

	<?php if ( isset( $rank_table->requests['used'], $rank_table->requests['limit'] ) && $rank_table->requests['used'] === $rank_table->requests['limit'] ) : ?>

		<div class="upgrade" id="upgrade-wide" >
			<div class="upgrade-wrapper">
				<div class="upgrade-content">
					<div class="upgrade-text-top">Uh-oh, it looks like you're out of keywords!</div>
					<div class="upgrade-text-bottom">Upgrade your plan to track more keywords and grow site traffic</div>
					<a class="button" href="https://www.wetpaintwebtools.com/plans/?utm_source=control-center-plugin-ad&utm_medium=in-app-advertisement&utm_content=all-keywords-uh-oh&utm_campaign=Upsell%20Ad" target="_blank">Upgrade Your Plan</a>
				</div>
			</div>
		</div>

	<?php endif; ?>

	<?php $rank_table->views(); ?>

	<form id="filter_action" action="" method="get">

	<input type="hidden" name="page" value="control-center-keyword-performance" />

	<?php $rank_table->search_box( 'Search', 'keyword' ); ?>

	<?php $rank_table->display(); ?>

	</form>

	<div id="ajax-response"></div>
	<br class="clear" />

	<?php
	if ( $rank_table->raw_items && count( $rank_table->raw_items) > 0 ) {

		$chart_colors = array (
			'#edc240',
			'#afd8f8',
			'#4da74d',
			'#7A92A3',
			'#0b62a4'
		);
		$count = 0;

		$data = array (
			'rank' => array (
				'label' => 'Today',
				'data' => array (
					'rank_1' => 0,
					'rank_2' => 0,
					'rank_3' => 0,
					'rank_4' => 0,
					'rank_5' => 0,
					'rank_6' => 0,
					'rank_7' => 0,
					'rank_8' => 0,
					'rank_9' => 0,
					'rank_10' => 0,
					'rank_page_2' => 0,
					'rank_page_3' => 0,
					'rank_page_4plus' => 0
				)
			)
		);

		foreach ( $rank_table->raw_items as $serps ) {

			if ( isset( $serps['serp'] ) ) {
				foreach ( $serps['serp'] as $serp ) {

					if ( isset( $serp['rank'] ) && $serp['rank'] > 0 ) {
						if ( $serp['rank'] > 30 ) {
							$data['rank']['data']['rank_page_4plus']++;
						}
						elseif ( $serp['rank'] > 20 ) {
							$data['rank']['data']['rank_page_3']++;
						}
						elseif ( $serp['rank'] > 10 ) {
							$data['rank']['data']['rank_page_2']++;
						}
						elseif ( $serp['rank'] <= 10 ) {
							$data['rank']['data']['rank_'. $serp['rank']]++;
						}
					}
				}
			}
		}

		$rankings = false;
		foreach ( $data['rank']['data'] as $data_item ) {
			if ( $data_item > 0 ) {
				$rankings = true;
			}
		}


		function chart_dates( $timestamp ) {
			if ( gmdate( 'Y', $timestamp ) != gmdate( 'Y', time() ) ) {
				echo gmdate( 'F j, Y', $timestamp );
			}
			else {
				echo gmdate( 'F j', $timestamp );
			}
		}

		if ( $rankings ) {
		?>
		<script>
		jQuery(function ($) {
			$('.chart').show();

			var width = $(".keyword-performance-page").width();

			$("#rankings-chart").width( width );
			$("#rankings-chart").height( 400 );

			var data = {
				"labels": [
					'Ranking #1',
					'Ranking #2',
					'Ranking #3',
					'Ranking #4',
					'Ranking #5',
					'Ranking #6',
					'Ranking #7',
					'Ranking #8',
					'Ranking #9',
					'Ranking #10',
					'Ranking on Page 2',
					'Ranking on Page 3',
					'Ranking on Page 4+',
				],
				"datasets":[
					<?php
					foreach ( $data as $series ) {
						echo '{ "data":';
							echo '[';
								foreach ( $series['data'] as $point ) {
									echo $point .',';
								}
							echo '],';
							echo '"label":"'. $series['label'] .'",';
							echo '"backgroundColor":"#0073AA",';
							echo '"borderColor":"'. $chart_colors[$count] .'",';
							echo '"pointBorderColor":"'. $chart_colors[$count] .'",';
							echo '"pointBackgroundColor":"'. $chart_colors[$count] .'",';
							echo '},';

							$count == count( $chart_colors ) - 1 ? $count = 0 : $count++;
					}
					?>
				]
			};

			var date_mod = Math.floor( data.labels.length / 8 );

			var options = {
				maintainAspectRatio: true,
				responsive: true,
				scales: {
					xAxes: [{
						display: true,
					}],
					yAxes: [{
						display: true,
					}]
				},
				tooltips: {
					enabled: true,
					backgroundColor: 'rgba( 255, 255, 255, 0.2 )',
					fontColor: '#333',
					fontSize: 12,
					titleFontSize: 14,
					titleFontColor: '#333',
					yPadding: 10,
					xPadding: 15,
					cornerRadius: 4,
					xOffset: 0,
				},
				animation: {
					duration: 0,
				}
			};

			$(window).load(function() {
				var chart = document.getElementById("rankings-chart");
				if ( chart ) {
					var ctx = document.getElementById("rankings-chart").getContext("2d");
					var myBarChart = new Chart(ctx, {
						type: 'bar',
						data: data,
						options: options
					});
				}
			});
		});
		</script>
	<?php
		}
	}
	?>
	<div id="ajax-response"></div>
</div>