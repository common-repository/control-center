<?php
/**
 * Display the active keywords on the site using the WP_List_Table class.
 *
 * @link       https://www.wetpaintwebtools.com/
 * @since      1.0.0
 *
 * @package    Keyword_Performance
 * @subpackage Keyword_Performance/admin
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    Keyword_Performance
 * @subpackage Keyword_Performance/admin
 * @author     WetPaint <support@wetpaintwebtools.com>
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'class-wp-list-table.php' ); // Pull a local copy of the WP_List_Table class.
}

class WP_Keyword_Performance_List_Table extends WP_List_Table {

	/**
	 * array of used requests and limits
	 *
	 * @since 1.0.0
	 */
	public $requests;

	/**
	 * array of all raw SERP data
	 *
	 * @since 1.0.0
	 */
	public $raw_items;

	/**
	 * Setup instance of the plugin
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => __( 'keyword' ),
			'plural'   => __( 'keywords' ),
			'ajax'     => false
		) );
	}

	/**
	 * Set ajax users permissions.
	 * Currently returns false as ajax is set to false in the constructor
	 *
	 * @since 1.0.0
	 */
	public function ajax_user_can() {
		return false;
	}

	/**
	 * No content response
	 *
	 * @since 1.0.0
	 */
	public function no_items() {
		if ( isset( $_GET['s'] ) ) {
			echo '<p>'. __( 'No keywords found matching "'. $_GET['s'] .'".' ) .'</p>';
			echo '<a href="/wp-admin/admin.php?page=control-center-keyword-performance" class="button button-primary button-large">Clear Search</a>';
		} elseif ( !empty( $this->requests['limit'] ) ) {
			echo '<p>'. __( 'No keywords found, dude.' ) .'</p>';
			echo '<a href="/wp-admin/admin.php?page=control-center-keyword-performance&action=add" class="button button-primary button-large">Add Your First Keyword</a>';
			echo '<p><em>or, <a href="/wp-admin/admin.php?page=control-center-keyword-performance&action=bulk-add">Bulk Add Keywords</a></em></p>';
		} else {
			$options = get_option('control_center_keyword_performance');
			if ( false != $options ) {
				if ( !isset( $options['api_key'] ) || trim( $options['api_key'] ) === '' ) {
					echo '<p>'. __( 'Ready to get started?' ) .'</p>';
					echo '<a href="https://www.wetpaintwebtools.com/plans/" target="_blank" class="button button-primary button-large">'. __( 'Sign Up For An API Key' ) .'</a>';
				} else {
					echo '<p>'. __( 'You\'re almost ready to go!' ) .'</p>';
					echo '<a href="https://www.wetpaintwebtools.com/plans/" target="_blank" class="button button-primary button-large">'. __( 'Sign Up for Keyword Plan' ) .'</a>';
				}
			}
		}
	}

	/**
	 * TODO: Review function.
	 *
	 * @since 1.0.0
	 */
	public function prepare_items() {

		$api = WP_Keyword_Performance_API::get_instance();

		$return_data = $api->get_keywords();

		$default_location = isset( $return_data['keywords'][0]['serp'][0]['region'] ) ? $return_data['keywords'][0]['serp'][0]['region'] : '';

		// Pre-processing
		if ( $return_data['keywords'] ) {
			foreach ( $return_data['keywords'] as $kw_key => $keywords ) {
				foreach ( $keywords['serp'] as $serp_key => $serp ) {

					// Calculate Mean & Standard Deviation (Volatility)
					$dates_array = array( 'rank', 'prev_rank', 'rank7', 'rank30', 'rank90' );
					$count = 0;
					$total = 0;

					foreach ( $dates_array as $date ) {
						if ( !is_null( $serp[ $date ] ) ) {
							$total += $serp[ $date ];
							$count++;
						}
					}

					if ( $count > 0 ) {

						$average_rank = $total / $count;

						$return_data['keywords'][ $kw_key ]['serp'][ $serp_key ]['average_rank'] = $average_rank;

						// Determine each period's deviation
						$deviation = 0;
						$total = 0;

						foreach ( $dates_array as $date ) {
							$item_deviation = pow( $serp[ $date ] - $average_rank, 2 );
							$total += $item_deviation;
						}

						$deviation = $total / $count;

						$return_data['keywords'][ $kw_key ]['serp'][ $serp_key ]['volatility'] = sqrt( $deviation );
					} else {
						$return_data['keywords'][ $kw_key ]['serp'][ $serp_key ]['average_rank'] = null;
						$return_data['keywords'][ $kw_key ]['serp'][ $serp_key ]['volatility'] = null;
					}

					// Determine if multiple keyword locations are used - if not, don't show that column
					if ( $serp['region'] != $default_location ) {
						$multiple_locations = true;
					}
				}
			}
		}

		if ( isset( $return_data['status'] ) && $return_data['status'] == 401 ) {
			echo '<div id="notice" class="notice notice-warning"><p id="has-newer-autosave"><strong>Error Connecting to the API</strong> The message from the server is "' . esc_html( $return_data['message'] ) . '". Please check your API settings on the Settings page</p></div>';
		}

		if ( $return_data && ( !isset( $return_data['status'] ) || $return_data['status'] != 401 ) ) {

			$this->requests = $return_data['requests'];

			if ( !empty( $_REQUEST['s'] ) ) {
				$search_return_data = array();
				foreach ( $return_data['keywords'] as $key => $value ) {
					if ( strpos( $value['keyword'], $_REQUEST['s'] ) != false ) {
						$search_return_data[] = $value;
					}
				}

				$return_data['keywords'] = $search_return_data;
			}

			// Get all the available statuses used in the data
			$this->get_status_links( $return_data['totals'] );

			// Get all the search engine used in the data.
			$this->get_search_engines( $return_data['keywords'] );

			/* FILTER DATA */

			// Filter by status
			if ( isset( $_GET['status'] ) && ( $_GET['status'] != 'all' ) ) {

				foreach ( (array) $return_data['keywords'] as $a => $keywords ) {

					if ( !empty( $keywords['serp'] ) ) {

						foreach ( $keywords['serp'] as $b => $serps ) {

							if ( $serps['status'] != $_GET['status'] ) {
								unset( $return_data['keywords'][ $a ]['serp'][ $b ] );
							}
						}
					}

					// Remove keywords with no results
					if ( empty( $return_data['keywords'][ $a ]['serp'] ) ) {
						unset($return_data['keywords'][ $a ]);
					}
				}
			}

			// Filter by engine
			if ( isset( $_GET['engine'] ) && ( $_GET['engine'] != 'all' ) ) {

				foreach ( $return_data['keywords'] as $a => $keywords ) {
					foreach ( $keywords['serp'] as $b => $serp ) {
						if ( $serp['engine'] != $_GET['engine'] ) {
							unset( $return_data['keywords'][ $a ]['serp'][ $b ] );
						}
					}

					if ( empty( $return_data['keywords'][ $a ]['serp'] ) ) {
						unset( $return_data['keywords'][ $a ] );
					}
				}
			}

			foreach ( $return_data['keywords'] as $kw_key => $keyword ) {
				usort( $return_data['keywords'][ $kw_key ]['serp'], array( $this, 'compare_location_id_ranks' ) );
			}

			// Order Array
			$orderby = isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'rank';
			$order = isset( $_GET['order'] ) ? $_GET['order'] : 'asc';

			switch ( $orderby ) {
				case 'rank':
				case 'change':
				case 'rank7':
				case 'rank30':
				case 'rank90':

					usort( $return_data['keywords'], function( $a, $b ) use ( $order, $orderby ) {

						$orderby = ( $orderby == 'change' ) ? 'prev_rank' : $orderby;

						// Get highest ranking among all SERPs within a keyword, then use that to compare
						$a = WP_Keyword_Performance_List_Table::get_highest_rank( $a, $orderby );
						$b = WP_Keyword_Performance_List_Table::get_highest_rank( $b, $orderby );

						if ($a == $b) {
							return 0;
						} elseif ( is_null( $a ) ) {
							return 1;
						} elseif ( is_null( $b ) ) {
							return -1;
						}

						if ( $order == 'asc' ) {
							return ( $a < $b ) ? -1 : 1;
						} else {
							return ( $a > $b ) ? -1 : 1;
						}

					} );

					break;
				case 'average_rank':

					usort( $return_data['keywords'], function( $a, $b ) use ( $order ) {

						// Get highest ranking among all SERPs within a keyword, then use that to compare
						$a = WP_Keyword_Performance_List_Table::get_highest_rank( $a, 'average_rank' );
						$b = WP_Keyword_Performance_List_Table::get_highest_rank( $b, 'average_rank' );

						if ($a == $b) {
							return 0;
						} elseif ( is_null( $a ) ) {
							return 1;
						} elseif ( is_null( $b ) ) {
							return -1;
						}

						if ( $order == 'asc' ) {
							return ( $a < $b ) ? -1 : 1;
						} else {
							return ( $a > $b ) ? -1 : 1;
						}

					} );

					break;
				case 'volatility':

					usort( $return_data['keywords'], function( $a, $b ) use ( $order ) {

						// Get highest ranking among all SERPs within a keyword, then use that to compare
						$a = WP_Keyword_Performance_List_Table::get_highest_rank( $a, 'volatility' );
						$b = WP_Keyword_Performance_List_Table::get_highest_rank( $b, 'volatility' );

						if ($a == $b) {
							return 0;
						} elseif ( is_null( $a ) ) {
							return 1;
						} elseif ( is_null( $b ) ) {
							return -1;
						}

						if ( $order == 'asc' ) {
							return ( $a < $b ) ? -1 : 1;
						} else {
							return ( $a > $b ) ? -1 : 1;
						}

					} );

					break;
				case 'page':

					foreach ( $return_data['keywords'] as &$keywords ) {

						foreach ( $keywords as &$serp ) {

							if ( is_array( $serp ) ) {
								usort( $serp, function( $a, $b ) use ( $order ){
									$cmp = strnatcmp( $a['href'], $b['href']);
									return $order == 'asc' ? -$cmp : $cmp;
								} );
							}
						}
					}

					usort( $return_data['keywords'], function( $a, $b ) use( $order ) {

						$a_href = ( isset( $a['serp'][0]['href'] ) ? $a['serp'][0]['href'] : '' );
						$b_href = ( isset( $b['serp'][0]['href'] ) ? $b['serp'][0]['href'] : '' );

						if ( $a_href == $b_href ) {
							return strnatcmp( $a['keyword'], $b['keyword'] );
						}

						$cmp = strnatcmp( $a_href, $b_href );
						return $order == 'asc' ? -$cmp : $cmp;

					} );

					break;
				default:
					// Sort by keyword and order
					usort( $return_data['keywords'], function ($a, $b) {
						return strnatcmp( $a['keyword'], $b['keyword'] );
					} );
					if ( 'desc' === $order ) {
						$return_data['keywords'] = array_reverse( $return_data['keywords'] );
					}
			}
		}

		// Set the column headers
		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Pagination
		$current_page = $this->get_pagenum();

		if ( !isset( $return_data['status'] ) || $return_data['status'] != 401 ) {
			$total_items = count( $return_data['keywords'] );
		}
		else {
			$total_items = 0;
		}

		$per_page = $this->get_items_per_page( 'edit_per_page' );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page
		) );

		if ( $return_data && ( !isset( $return_data['status'] ) || $return_data['status'] != 401 ) ) {
			// Slice the items down to the page
			$page_start = ( $current_page - 1 ) * $per_page;

			$this->raw_items = $return_data['keywords'];
			$this->items = array_slice($return_data['keywords'], $page_start, $per_page);
		}
	}

	/**
	 * Fetch the highest ranking for a specified keyword
	 *
	 * @since 1.0.0
	 */

	public static function get_highest_rank( $keyword, $field ) {
		if ( isset( $keyword['serp'][0] ) ) {
			$highest_rank = $keyword['serp'][0][ $field ];
			foreach ( $keyword['serp'] as $serp ) {
				if ( !is_null( $serp[ $field ] ) ) {
					if ( (float) $serp[ $field ] < $highest_rank ) {
						$highest_rank = (float)$serp[ $field ];
					}
				}
			}
			return $highest_rank;
		}
		return null;
	}

	/**
	 * Collect the search engines and results count for the get_views function.
	 *
	 * @since 1.0.0
	 */
	public function get_status_links( $totals ) {

		// We don't need all in the totals, WordPress takes care of that for us
		if ( isset($totals['all']) ) {
			unset($totals['all']);
		}

		$this->screen->views_links = $totals;
	}

	/**
	 * Collect the search engines and results count for the get_views function.
	 *
	 * @since 1.0.0
	 */
	public function get_search_engines( $keywords ) {

		$search_engines = array();

		// Collect all the different engine tracking keywords
		foreach ( $keywords as $keyword ) {

			foreach ( (array) $keyword as $serps ) {

				foreach ( (array) $serps as $serp ) {

					if ( isset( $serp['engine'] ) ) {
						$search_engines[] = $serp['engine'];
					}
				}
			}
		}

		$search_engines = array_count_values( $search_engines );

		$this->screen->search_engines = $search_engines;
	}

	/**
	 * Collect the status of each serp to tell if the over all keyword is active, paused or trashed.
	 *
	 * @since 1.0.0
	 */
	public function get_serp_status( $serps ) {
		// Collect all the different engine tracking keywords
		foreach ( (array) $serps as $serp ) {
			if ( isset( $serp['status'] ) ) {
				$serp_status[] = $serp['status'];
			}
		}

		if ( isset( $serp_status ) && is_array( $serp_status ) ){
			$serp_status = array_count_values( $serp_status );
			return $serp_status;
		} else {
			return false;
		}

	}

	/**
	 * Add table columns.
	 *
	 * @since 1.0.0
	 */
	public function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'cb':
			case 'keyword':
			case 'location':
			case 'rank':
			case 'rank7':
			case 'rank30':
			case 'rank90':
			case 'change':
			case 'engine':
			case 'page':
				return $item[ $column_name ];
			default:
				return false;
				//return print_r( $item, true ) ; // Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Set which table columns user can sort by.
	 *
	 * @since 1.0.0
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'keyword' => array( 'keyword', false ),
			'rank'    => array( 'rank', false ),
			'change'  => array( 'change', false ),
			'rank7'  => array( 'rank7', false ),
			'rank30'  => array( 'rank30', false ),
			'rank90'  => array( 'rank90', false ),
			'average_rank' => array( 'average_rank', false ),
			'volatility' => array( 'volatility', false ),
			'page'    => array( 'page', false ) // TODO: Should this bee a sort-able column?
		);
		return $sortable_columns;
	}

	/**
	 * Add table columns.
	 * This must be define in WP_LIST_TABLE extended class.
	 *
	 * @since 1.0.0
	 */
	public function get_columns() {
		$columns = array(
			'cb'       => '<input type="checkbox" />',
			'keyword'  => __( 'Keywords' ),
			'location' => __( 'Location' ),
			'rank'     => __( 'Rank' ),
			'change'   => 'Yesterday',
			'rank7'    => __( 'Week' ),
			'rank30'   => __( 'Month' ),
			'rank90'   => __( '3 Months' ),
			'average_rank' => __( 'Average' ),
			'volatility' => __( 'Volatility' ),
			'engine'   => __( 'Engine' ),
			'page'     => __( 'Page' )
		);
		return $columns;
	}

	public function get_hidden_columns() {
		if ( isset( $multiple_locations ) ) {
			$columns = array();
		}
		else {
			$columns = array(
				'location'
			);
		}

		return $columns;
	}

	/**
	 * Create the view filter for by search engine
	 *
	 * @since    1.0.0
	 * @access protected
	 */
	protected function get_views() {

		$status = isset( $_GET['status'] ) ? $_GET['status'] : 'all';
		isset( $this->screen->views_links ) ? $serp_status = $this->screen->views_links : '';
		$parent_page = $this->screen->parent_base;
		isset( $serp_status ) ? $total_results = array_sum( $serp_status ) : $total_results = 0;
		// Default statuses in the correct order
		$defulat_status = array();

		$class = '';

		if ( !isset( $_GET['status'] ) || ( $_GET['status'] == 'all' ) ) {
			$class = ' class="current"';
		}

		// Add the all link and count to the links
		$status_links['all'] = "<a href='" . admin_url( "admin.php?page=$parent_page&status=all" ) . "'$class>" . sprintf( _nx( 'All Keywords <span class="count">(%s)</span>', 'All Keywords <span class="count">(%s)</span>', $total_results, 'All the keyword entries' ), number_format_i18n( $total_results ) ) . '</a>';

		// Loop through the search engines and build the filter links
		foreach ( $defulat_status as $key ) {

			$class = '';

			if ( isset( $_GET['status'] ) && ( $_GET['status'] == $key ) ) {
				$class = ' class="current"';
			}

			if ( isset($serp_status[$key]) ) {
				$number = $serp_status[$key];
			} else {
				$number = 0;
			}
			$status_links[$key] = "<a href='" . admin_url( "admin.php?page=$parent_page&status=$key" ) . "'$class>" . sprintf( '%s <span class="count">(%s)</span>', ucwords( $key ), number_format_i18n( $number ) ) . '</a>';
		}

		return $status_links;
	}

	/**
	 * TODO: Review function.
	 *
	 * @since 1.0.0
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => 'Delete'
		);
		return $actions;
	}

	/**
	 * TODO: Review function.
	 *
	 * @since 1.0.0
	 */
	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {
			wp_die('Items deleted (or they would be if we had items to delete)!');
		} elseif ( 'paused' === $this->current_action() ) {
			wp_die('Items paused (or they would be if we had items to delete)!');
		}

	}

	/**
	 * Add the table filters
	 *
	 * @since 1.0.0
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {

		// Search engine filter
		$engine = isset( $_GET['engine'] ) ? $_GET['engine'] : 'all';
		isset( $this->screen->search_engines ) ? $search_engines = $this->screen->search_engines : ''; // Collect the search engines with the SERPs count for those engines
		isset( $search_engines ) ? $total_results = array_sum( $search_engines ) : ''; // Get the total for all the SERPs being tracked
		if ( isset( $search_engines ) ) {
			ksort( $search_engines ); // Sort the search engines alphabetical
		}

		echo '<div class="alignleft actions">';

		if ( 'top' == $which ) {

			// Search engine filters
			echo '<label for="filter-by-engine" class="screen-reader-text">Filter By Search Engine</label>';

			echo '<select name="engine" id="filter-by-engine">';

				echo '<option' . selected( $engine, 'all' ) . ' value="all">All Search En</span></option>';

				if ( isset( $search_engines ) ) {
					foreach ( $search_engines as $key => $value ) {
						echo '<option' . selected( $engine, $key ) . ' value="' . $key . '">' . ucwords( $key ) . '</option>';
					}
				}

			echo '</select>';

			submit_button( __( 'Filter' ), 'button', 'filter_action', false );

			if ( !empty( $this->requests['limit'] ) ) {
				echo '<span class="my-account-remaining"><em>' . WP_Keyword_Performance::format_requests_remaining( $this->requests['limit'], $this->requests['used'] ) . ' Keyword Requests Remaining</em></span>';
			} else {
				// No request limit indicates a problem with the WetPaint account
			}
		}

		echo '</div>';
	}


	public function current_action() {
		return parent::current_action();
	}

	/**
	 * Display the table rows
	 *
	 * @since    1.0.0
	 */
	public function display_rows() {
		// Get the rows registered in the prepare_items method
		$rows = $this->items;
		if ( ! empty( $rows ) ) {
			$row_count = 0;
			// Loop through each row
			foreach ( $rows as $row ) {
				$row_count++;
				$row_class = "hentry row-$row_count";

				$serp_status = $this->get_serp_status( $row['serp'] );
				$total_status = array_sum( (array) $serp_status );

				$row_class .= ' active-keyword';

				echo "<tr id='keyword-${row['id']}' class='$row_class'>";
					$this->single_row( $row );
				echo "</tr>";
			}
		}
	}

	/**
	 * Resort so active keywords show up first
	 *
	 * @since    1.0.0
	 */
	public function resort_serps($a, $b) {

		// a and b are equal, return 0 for no change
		if ( $a['status'] === $b['status'] ) {
			return 0;
		}

		// a is active return 1 to bump it up
		if ( $a['status'] === 'active' ) {
			return -1;
		}

		// b is active return -1 to bump a down
		if ( $b['status'] === 'active' ) {
			return 1;
		}

		// didn't match any condition so just return 0 because we don't care
		return 0;
	}

	/**
	 * Order Location ID's by Rank Automatically
	 *
	 * @since    1.0.0
	 */
	public function compare_location_id_ranks( $a, $b ) {
		$a['rank'] = ( $a['rank'] == null ? 150 : $a['rank'] );
		$b['rank'] = ( $b['rank'] == null ? 150 : $b['rank'] );

		if ( $a['rank'] == $b['rank']) {
			return 0;
		}

		return ($a['rank'] > $b['rank']) ? 1 : -1;
	}

	/**
	 * @since    1.0.0
	 */
	public function single_row( $row ) {

		// Load the array items into variable (so if API changes it is easier to update)
		$id = $row['id'];
		$keyword = $row['keyword'];
		$serps = $row['serp'];

		// Disabled for now since we don't arn't using statuses yet
		//~ if ( is_array( $serps ) ) {
			//~ usort( $serps, array( $this, 'resort_serps' ));
		//~ }

		//TODO: update the action to check the status of the location under this keyword group
		$status_class = ' ';

		// Get the columns registered in the get_columns and get_sortable_columns methods
		list( $columns, $hidden ) = $this->get_column_info();

		// Loop for each row's columns
		foreach ( $columns as $column_name => $column_display_name ) {

			$class = "class='$column_name-cell'";
			$style = "";

			if ( in_array( $column_name, $hidden ) ) {
				$style = ' style="display:none;"';
			}
			$attributes = $class . $style;

			switch( $column_name ){
				case 'cb': ?>

					<th scope="row" class="check-column">
						<label class="screen-reader-text" for="cb-select-<?php echo $id; ?>"><?php _e( 'Select ' . $keyword ); ?></label>
						<input id="cb-select-<?php echo $id; ?>" type="checkbox" name="keyword[]" value="<?php echo $id; ?>" />
					</th>

				<?php break;
				case 'keyword': ?>

					<td <?php echo $attributes; ?>>

						<div class="row-keyword">
							<strong><a href="<?php echo esc_url( add_query_arg( array ( 'keyword' => $row['id'], 'action' => 'edit' ) ) ); ?>" title="Edit &#8220;<?php _e( $keyword ); ?>&#8221;" ><?php _e( $keyword ); ?></a></strong>
						</div>

						<div class="row-actions">

						<?php
						$actions['edit'] = '<a href="'. esc_url( add_query_arg( array( 'keyword' => $row['id'], 'action' => 'edit' ) ) ) .'" title="' . esc_attr__( 'Edit this item' ) . '">' . __( 'Edit' ) . '</a>';

						$actions['delete'] = '<a href="'. esc_url( wp_nonce_url( add_query_arg( array( 'keyword' => $row['id'], 'action' => 'delete' ) ), 'delete_keyword' ) ) .'" title="' . esc_attr__( 'Edit this item' ) . '">' . __( 'Delete' ) . '</a>';

						foreach ( (array) $serps as $serp ) {

							if ( isset( $serp['status'] ) ) {
								$keyword_status[] = $serp['status'];
							}
						}

						if ( isset( $keyword_status ) && in_array( 'active', (array) $keyword_status ) ) {
							//$actions['pause'] = '<a href="TODO: Add URL" title="' . esc_attr__( 'Pause this item' ) . '">' . __( 'Pause' ) . '</a>';
						} else {
							$actions['unpause'] = '<a href="TODO: Add URL" title="' . esc_attr__( 'Unpause this item' ) . '">' . __( 'Unpause' ) . '</a>';
						}

						echo $this->row_actions( $actions );
						?>

						</div>

					</td>

				<?php break;
				case 'location': ?>

					<td <?php echo $attributes; ?>>

						<div class="row-locations">

						<?php // Loop through and display the order location // TODO: Make sure that the locations are in the correct order with the other columns
						if ( ! empty( $serps ) ) {

							echo '<ul class="locations">';

							foreach ( (array) $serps as $serp ) {

								if ( ! empty( $serp['geo'] ) ) {

									echo '<li class="location geographic ' . $serp['status'] . '">' . $serp['geo'] . '</li>'; // TODO: Create a function to take the geo and return the locations name

								} else {
									$engine_variable = $serp['engine'] . '_locations';
									$locale = WP_Keyword_Performance::$$engine_variable;
									echo '<li class="location regional ' . $serp['status'] . '">' . __( $locale[ $serp['region'] ] ) . '</li>';

								}
							}

							echo '</ul>';

						}
						?>

						</div>

					</td>

				<?php break;
				case 'rank': ?>

					<td <?php echo $attributes; ?>>

						<div class="row-top-ranks">

						<?php // Loop through and display the current rank
						if ( ! empty( $serps ) ) {

							echo '<ul class="ranks">';

							foreach ( (array) $serps as $serp ) {

								if ( $serp['last_rank_date'] == '0000-00-00' ) {
									echo '<li class="rank"><span class="dashicons dashicons-update"></span></li>';
								}
								elseif ( $serp['rank'] == 0 ) {
									echo '<li class="rank"><span class="dashicons dashicons-hidden"></span></li>';
								}
								else {
									echo "<li class='rank'>${serp['rank']}</li>";
								}

							}

							echo '</ul>';

						}
						?>

						</div>

					</td>

				<?php break;
				case 'change': ?>

					<td <?php echo $attributes; ?>>

						<div class="row-change">

						<?php // Loop through ranks and display the changes
						if ( ! empty( $serps ) ) {

							echo '<ul class="changes">';

							foreach ( (array) $serps as $serp ) {

								$rank_dif = $serp['rank'] - $serp['prev_rank'];

								if ( ( $serp['last_rank_date'] == '0000-00-00' ) || ( strtotime( $serp['created_date'] ) > strtotime( "-1 days" ) ) ) {
									$rank_dif = '<span class="dashicons dashicons-update"></span>';
									$change_class = ' no-change ';
									$change_title = 'No Data For This Period';
								} elseif ( 0 === $serp['prev_rank'] || is_null( $serp['prev_rank'] ) || is_null( $serp['rank'] ) ) {
									$rank_dif = '<span class="dashicons dashicons-hidden"></span>';
									$change_class = ' no-change ';
									$change_title = 'Not Ranking';
								} elseif ( 0 === $rank_dif ) {
									$rank_dif = '';
									$change_class = ' no-change';
									$change_title = 'There is no change in rank or no previous rank to compare.';
								} elseif ( $rank_dif < 0 ) {
									$rank_dif = '<span class="change">' . abs($rank_dif) . '</span>';
									$change_class = ' positive ';
									$change_title = 'The page has moved UP from ' . $serp['prev_rank'] . ' since yesterday.';
								} else {
									$rank_dif = '<span class="change">' . abs($rank_dif) . '</span>';
									$change_class = ' negative ';
									$change_title = 'The page has moved DOWN from ' . $serp['prev_rank'] . ' since yesterday.';
								}

								$change_class .= $serp['status']; // Add status class to the change_class

								echo "<li class='change$change_class' title='$change_title'>" . $serp['prev_rank'] . $rank_dif . "</li>";

							}

							echo '</ul>';

						}
						?>

						</div>

					</td>


				<?php break;
				case 'rank7':
				case 'rank30':
				case 'rank90':
				?>

					<td <?php echo $attributes; ?>>

						<div class="row-top-ranks">

						<?php // Loop through and display the current rank
						if ( ! empty( $serps ) ) {

							$days_since = substr( $column_name, 4 );
							if ( $days_since == 7 ) {
								$days_since = '1 week';
							} else {
								$days_since = $days_since .' days';
							}

							echo '<ul class="ranks">';

							foreach ( (array) $serps as $serp ) {
								$rank_dif = $serp['rank'] - $serp[$column_name];

								if ( is_null( $serp[$column_name] ) && ( ( $serp['last_rank_date'] === '0000-00-00' ) || ( strtotime( $serp['created_date'] ) > strtotime( "-$days_since" ) ) ) ) {
									$rank_dif = '<span class="dashicons dashicons-update"></span>';
									$change_class = ' no-change ';
									$change_title = 'No Data For This Period';
								} elseif ( $serp[$column_name] == 0 || is_null( $serp['rank'] ) || is_null( $serp[$column_name] ) ) {
									$rank_dif = '<span class="dashicons dashicons-hidden"></span>';
									$change_class = ' no-change ';
									$change_title = 'Not Ranking';
								} elseif ( 0 === $rank_dif ) {
									$rank_dif = '';
									$change_class = ' no-change ';
									$change_title = 'There is no change in rank or no previous rank to compare.';
								} elseif ( $rank_dif < 0 ) {
									$rank_dif = '<span class="change">' . abs($rank_dif) . '</span>';
									$change_class = ' positive ';
									$change_title = 'The page has moved UP from ' . $serp[$column_name] . ' over the past '. $days_since .'.';
								} else {
									$rank_dif = '<span class="change">' . abs($rank_dif) . '</span>';
									$change_class = ' negative ';
									$change_title = 'The page has moved DOWN from ' . $serp[$column_name] . ' over the past '. $days_since .'.';
								}

								$change_class .= $serp['status']; // Add status class to the change_class

								echo "<li class='change$change_class' title='$change_title'>" . $serp[$column_name] . $rank_dif . "</li>";

							}

							echo '</ul>';

						}
						?>

						</div>

					</td>

				<?php break;
				case 'average_rank': ?>

					<td <?php echo $attributes; ?>>

						<div class="row-change">

						<?php // Loop through ranks and display the changes
						if ( ! empty( $serps ) ) {

							echo '<ul class="changes">';

							foreach ( (array) $serps as $serp ) {

								if ( !is_null( $serp['average_rank'] ) ) {
									echo "<li class='change$change_class' title='$change_title'>". (float)number_format( $serp['average_rank'], 1 ) ."</li>";
								} else {
									echo "<li class='change no-change active' title='Not Ranking'><span class='dashicons dashicons-hidden'></span></li>";
								}

							}

							echo '</ul>';

						}
						?>

						</div>

					</td>

				<?php break;
				case 'volatility': ?>

					<td <?php echo $attributes; ?>>

						<div class="row-change">

						<?php // Loop through ranks and display the changes
						if ( ! empty( $serps ) ) {

							echo '<ul class="changes">';

							foreach ( (array) $serps as $serp ) {

								if ( !is_null ( $serp['volatility'] ) ) {
									echo "<li class='change$change_class' title='$change_title'>". (float)number_format( $serp['volatility'], 1 ) ."</li>";
								} else {
									echo "<li class='change no-change active' title='Not Ranking'><span class='dashicons dashicons-hidden'></span></li>";
								}

							}

							echo '</ul>';

						}
						?>

						</div>

					</td>

				<?php break;
				case 'engine': ?>

					<td <?php echo $attributes; ?>>

						<div class="row-engine">

						<?php // Loop through and display the search engine
						if ( ! empty( $serps ) ) {

							echo '<ul class="engines">';

							foreach ( (array) $serps as $serp ) {
								echo '<li class="engine ' . $serp['status'] . '">' . ucwords( $serp['engine'] ) . '</li>'; // TODO: Replace name with logo? from font awesome?
							}

							echo '</ul>';

						}
						?>

						</div>

					</td>

				<?php break;
				case 'page': ?>

					<td <?php echo $attributes; ?>>

						<div class="row-page">

						<?php // Loop through and display the page URL // TODO: Does this need to be formatted?
						if ( ! empty( $serps ) ) {

							echo '<ul class="pages">';

							foreach ( (array) $serps as $serp ) {

								if ( isset($serp['url']) ) {
									echo '<li class="page ' . $serp['status'] . '"><a href="' . esc_url( $serp['url'] ) . '" target="_blank" title="View Page">' . esc_html( $serp['url'] ) . '</a></li>';
								} else {
									echo '<li class="page">&nbsp;</a>';
								}

							}

							echo '</ul>';

						}
						?>

						</div>

					</td>

				<?php break;
			}
		}
	}
}
