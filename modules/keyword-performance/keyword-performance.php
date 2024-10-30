<?php
/**
 * The Keyword Performance Module
 *
 * @link       www.wetpaint.io
 * @since      1.0.0
 * @package    Control_Center
 * @subpackage Control_Center/modules/keyword-performance
 * @author     WetPaint <support@wetpaintwebtools.com>
 */
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'WP_Keyword_Performance_API' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'includes/class-keyword-performance-api.php' );
}

class WP_Keyword_Performance {

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @var		object
	 */
	private static $instance = null;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since 1.0.0
	 * @access   protected
	 * @var      Keyword_Performance_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since 1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since 1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Notices for keyword actions
	 *
	 * @since 1.0.0
	 * @access   private
	 * @var      string    $notices    Notices for keyword actions.
	 */
	private $notices;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the Dashboard and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$cc = Control_Center::get_instance();

		$this->plugin_name = $cc->get_plugin_name();
		$this->version = $cc->get_version();

		$this->define_hooks();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @return	 object	 A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Register all of the hooks related to the dashboard functionality of the plugin.
	 *
	 * @since 1.0.0
	 * @access   private
	 */
	private function define_hooks() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'admin_init', array( $this, 'process_forms' ) );
		add_action( 'admin_menu', array( $this, 'add_pages' ), 11 );
		add_action( 'admin_init', array( $this, 'keyword_settings_init' ) );
		add_action( 'control_center_settings', array( $this, 'keyword_settings_page' ) );

		$options = get_option('control_center_keyword_performance');
		if ( false !== $options ) {
			if ( ! isset( $options['api_key'] ) || trim( $options['api_key'] ) === '' ) {
				add_action( 'admin_notices', array( $this, 'enter_api_keys' ), 10 );
			}
		}

	}

	/**
	 * Register the stylesheets for the Dashboard.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		$screen = get_current_screen();

		$keywords_screen = $this->keywords_screen;
		if ( is_object( $keywords_screen ) ) {
			$keywords_screen = $keywords_screen->id;
		}

		if ( $screen->id == $keywords_screen ) {

			wp_enqueue_style( $this->plugin_name . '_keyword-performance', plugin_dir_url( __FILE__ ) . 'css/keyword-performance-admin.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name . '_datepicker', plugin_dir_url( __FILE__ ) . 'css/datepicker.css', array(), $this->version, 'all' );

		}

		if ( $screen->id == 'toplevel_page_keyword_performance' && isset( $_GET['keyword'] ) ) {
			wp_enqueue_style( 'dashicons' );
		}
	}

	/**
	 * Register the JavaScript for the dashboard.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		$screen = get_current_screen();

		$keywords_screen = $this->keywords_screen;
		if ( is_object( $keywords_screen ) ) {
			$keywords_screen = $keywords_screen->id;
		}

		$options = get_option('control_center_keyword_performance');

		if ( isset( $options['api_key'] ) ) {
			// Only load js file within plugin pages
			if ( in_array( $screen->id, array(
				'toplevel_page_control-center',
				'center_page_control-center-keyword-performance',
				'center_page_control-center-settings'
			) ) ) {
				wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/keyword-performance-admin.js', array( 'jquery' ), $this->version, false );
			}
		}

		if ( $screen->id == $keywords_screen ) {

			if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'add', 'bulk-add') ) ) {
				wp_enqueue_script( $this->plugin_name . '_jquery_repeater', plugin_dir_url( __FILE__ ) . 'js/jquery.repeater.min.js', array( 'jquery' ), $this->version, false );
				wp_enqueue_script( $this->plugin_name . '_add_keyword', plugin_dir_url( __FILE__ ) . 'js/add-keyword.js', array( 'jquery' ), $this->version, false );
			}
			elseif ( isset( $_GET['keyword'] ) ) {
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_script( $this->plugin_name . '_jquery_repeater', plugin_dir_url( __FILE__ ) . 'js/jquery.repeater.min.js', array( 'jquery' ), $this->version, false );
				wp_enqueue_script( $this->plugin_name . '_add_keyword', plugin_dir_url( __FILE__ ) . 'js/edit-keyword.js', array( 'jquery' ), $this->version, false );
			}
			wp_enqueue_script( $this->plugin_name . '_chartjs', plugin_dir_url( __FILE__ ) . 'js/Chart.js', array(), '2.0.0-alpha', false );
		}
	}

	/**
	 * Add the admin pages and menu links.
	 *
	 * @since 1.0.0
	 */
	public function add_pages() {

		$this->keywords_screen = add_menu_page(
			'SEO Keywords',
			'SEO Keywords',
			'manage_options',
			'control-center-keyword-performance',
			array( $this, 'keyword_performance_report_page' ),
			'none',
			'3.38524'
		);

		// Add Help and Screen Options tabs to page
		add_action( "load-{$this->keywords_screen}", array ( &$this, 'create_options_help_screen' ) );
	}

	/**
	 * Add Options and Help to the admin Page
	 *
	 * @since 1.0.0
	 */
	public function create_options_help_screen() {
		// Create the WP_Screen object against your admin page handle. This ensures we're working with the right admin page
		$this->keywords_screen = WP_Screen::get($this->keywords_screen);

		$options = get_option('control_center_keyword_performance');
		if ( isset( $options['api_key'] ) ) {
			$this->keywords_screen->add_help_tab( array(
				'id'       => 'overview',
				'title'    => __( 'Overview' ),
				'content'  => '<p><em>Need help, or have a suggestion?</em> We\'d love to hear from you!</p><a href="https://www.wetpaintwebtools.com/contact-us/" target="_blank" class="button button-primary button-large">Contact Us</a>'
			));
		}

		// Add Options
		$this->keywords_screen->add_option(
			'per_page',
			array(
				'label' => 'Entries per page',
				'default' => 20,
				'option' => 'edit_per_page'
			)
		);
	}


	/**
	 * Add notice prompt to generate API Keys
	 *
	 * @since 1.0.2
	 */
	public function enter_api_keys() {
		echo '<div id="notice" class="updated notice"><p><strong>Welcome to the SEO Control Center</strong><br />Generate an API Key to begin tracking keywords!</p><p><a href="'. Control_Center::new_account_link() .'" target="_blank" class="button button-primary button-large">Get Started</a></p></div>';
	}


	/**
	 * Add options fields to Control Center settings page
	 *
	 * @since 1.0.0
	 */
	public function keyword_settings_page() {
		// TODO: build form in seo control center plugin so only settings are added here (so other modules can add settings fields without creating multiple forms
		echo '<form action="options.php" method="POST">';
			settings_fields( 'control-center-settings' );
			do_settings_sections( 'control-center-settings' );
			submit_button();
		echo '</form>';
	}

	public function keyword_settings_init() {
		add_settings_section(
			'keyword_settings',
			'Keyword Performance',
			array( $this, 'keyword_settings_callback'),
			'control-center-settings'
		);

		add_settings_field(
			'control_center_keyword_performance_site',
			'Site',
			array( $this, 'keyword_performance_site_callback'),
			'control-center-settings',
			'keyword_settings'
		);

		add_settings_field(
			'control_center_keyword_performance_api_key',
			'API Key',
			array( $this, 'keyword_performance_api_key_callback'),
			'control-center-settings',
			'keyword_settings'
		);

		register_setting( 'control-center-settings', 'control_center_keyword_performance', array( $this, 'control_center_keyword_performance_sanitize' ) );
	}

	public function keyword_settings_callback() {
		echo '<p>Add your API Key to start tracking keyword performance.</p>';
		echo '<p>Don\'t have an API Key? Sign up for an account to <a href="' . Control_Center::new_account_link() . '" target="_blank" title="Generate API Keys">generate one.</a></p>';
	}

	public function keyword_performance_api_key_callback() {
		$option = get_option( 'control_center_keyword_performance' );
		if ( ! isset( $option['api_key'] ) ) {
			$option['api_key'] = '';
		}
		echo '<input id="api_key" name="control_center_keyword_performance[api_key]" size="40" type="text" value="' . esc_attr( $option['api_key'] ) . '" />';
	}

	public function keyword_performance_site_callback() {
		$option = get_option( 'control_center_keyword_performance' );
		if ( ! isset( $option['site'] ) ) {
			$option['site'] = '';
		}
		echo '<input id="site" name="control_center_keyword_performance[site]" size="40" type="text" value="' . esc_attr( $option['site'] ) . '" />';
	}

	public function control_center_keyword_performance_sanitize( $input ) {
		$input['site'] = sanitize_text_field( $input['site'] );
		$input['api_key'] = sanitize_text_field( $input['api_key'] );

		if ( strlen( $input['site'] ) > 0 && strlen( $input['api_key'] ) > 0 ) {
			$api = WP_Keyword_Performance_API::get_instance();
			$api->set_api_keys( $input['site'], $input['api_key'] );
			$api->invalidate_transients();
			$api_key_validation = $api->get_account_limits();
			if ( $api_key_validation === false || ( isset( $api_key_validation['status'] ) && $api_key_validation['status'] == 401 ) ) {
				add_settings_error( 'control_center_keyword_performance', 'control_center_api_error', 'Invalid API Keys: Please recopy these from your account and try again.' );
			}
		}
		else {
			add_settings_error( 'control_center_keyword_performance', 'control_center_api_error', 'No API Keys Entered.' );
		}

		return $input;
	}

	/**
	 * Add the Keyword Performance admin classes and display function for the admin.
	 *
	 * @since 1.0.0
	 */
	public function keyword_performance_report_page() {
		if ( isset( $_GET['keyword'] ) && isset ( $_GET['action'] ) && $_GET['action'] == 'edit' ) {
			include( plugin_dir_path( __FILE__ ) . 'partials/single-keyword-display.php' );
		} elseif ( isset ( $_GET['action'] ) && $_GET['action'] == 'add' ) {
			include( plugin_dir_path( __FILE__ ) . 'partials/add-keyword.php' );
		} elseif ( isset ( $_GET['action'] ) && $_GET['action'] == 'bulk-add' ) {
			include( plugin_dir_path( __FILE__ ) . 'partials/bulk-add-keywords.php' );
		} else {
			if ( ! class_exists( 'WP_Keyword_Performance_List_Table' ) ) {
				// Load WP_List_Table class extension
				require_once(  plugin_dir_path( __FILE__ ) . 'includes/class-all-keywords-table.php' );
			}
			$rank_table = new WP_Keyword_Performance_List_Table();

			$rank_table->prepare_items();

			include( plugin_dir_path( __FILE__ ) . 'partials/all-keywords.php' );
		}
	}

	/**
	 * Process the forms submitted
	 *
	 * @since 1.0.0
	 */
	public function process_forms() {

		if ( current_user_can( 'manage_options' ) && isset( $_GET['wetpaint_sitename'] ) ) {
			update_option( 'control_center_keyword_performance', array(
				'site' => sanitize_text_field( $_GET['wetpaint_sitename'] ),
				'api_key' => sanitize_text_field( $_GET['wetpaint_apikey'] )
			) );
			wp_redirect( admin_url( 'admin.php?page=control-center-keyword-performance&wetpaint_notice=newkey' ) );
			exit;
		}

		if ( isset ( $_GET['page'] ) && strpos( $_GET['page'], 'control-center' ) !== false ) {

			if ( isset( $_GET['wetpaint_notice'] ) ) {
				if ( 'newkey' == $_GET['wetpaint_notice'] ) {
					$api = WP_Keyword_Performance_API::get_instance();
					$api->invalidate_transients();
					$api_key_validation = $api->get_account_limits();
					if ( $api_key_validation === false || ( isset( $api_key_validation['status'] ) && $api_key_validation['status'] == 401 ) ) {
						$this->notices .= '<div id="notice" class="notice notice-warning"><p>Your API keys have been saved, but there was an error connecting to the server. Please check the keys on the Settings page.</p></div>';
					}
					else {
						$this->notices .= '<div id="message" class="updated notice notice-success is-dismissible below-h2"><p>Your account has been successfully connected. You can now begin adding keywords.</p></div>';
					}
				}
			}

			if ( isset( $_POST['_wpnonce'] ) ) {
				$api = WP_Keyword_Performance_API::get_instance();

				if ( wp_verify_nonce( $_POST['_wpnonce'], $this->plugin_name . '_add_keyword' ) ) {
					$action = 'add';
				} elseif ( wp_verify_nonce( $_POST['_wpnonce'], $this->plugin_name . '_bulk_add_keyword' ) ) {
					$action = 'bulk-add';
				} elseif ( wp_verify_nonce( $_POST['_wpnonce'], $this->plugin_name . '_edit_keyword' ) ) {
					$action = 'edit';
					$keyword_id = $_GET['keyword'];
					$data = $api->get_keyword( $keyword_id );
					$keywords = $data['keyword']['serp'];
				}

				if ( isset( $_POST['request'] ) ) {
					$requests = array();
					$adds = array();
					$duplicate = false;

					foreach ( $_POST['request'] as $keyword_request ) {
						$key = false;

						// Get new requests - these won't have a location_id
						if ( empty ( $keyword_request['location_id'] ) ) {
							$key = $keyword_request['engine'].$keyword_request['region_' . $keyword_request['engine']];
							$adds[] = $keyword_request;
						}

						if ( $key === false ) {
							// This should only happen if data is messed up somewhere
							$this->notices .= "Keys got messed up somehow";
							$duplicate = true; // Set to true to not process the rest
						}
						else {
							if ( in_array( $key, $requests ) ) {
								$duplicate = true;
								continue;
							}
							else {
								$requests[] = $key;
							}
						}
					}

					if ( $duplicate ) {
						$this->notices .= '<div id="notice" class="notice notice-warning"><p id="has-newer-autosave"><strong>Duplicate keyword requests.</strong> Please choose a unique combination of search engine and region.</p></div>';
					}
					else {
						// Add new locations
						foreach( $adds as $keyword ) {
							$status['false'] = 0;
							$status['success'] = 0;

							if ( $action == 'bulk-add' ) {
								$bulk_keywords = array_filter( explode( "\n", $_POST['keyword'] ) );

								foreach ( $bulk_keywords as $bulk_keyword ) {
									$status_single = $api->add_keyword( $bulk_keyword, $keyword['engine'], $keyword['region_'.$keyword['engine']] );

									if ( $status_single === false ) {
										$status['false']++;
									}
									elseif ( isset( $status_single['status'] ) && $status_single['status'] == 200 ) {
										$status['success']++;
									}
									elseif ( isset ( $status_single['message'] ) ) {
										$this->notices .= '<div id="notice" class="notice notice-warning"><p>' . $status_single['message'] . '</p></div>';
									}
									else {
										$this->notices .= '<div id="notice" class="notice notice-warning"><p>An unknown error occurred.</p></div>';
									}
								}
							}
							else {
								$status_single = $api->add_keyword( $_POST['keyword'], $keyword['engine'], $keyword['region_'.$keyword['engine']] );
								if ( $status_single === false ) {
									$status['false']++;
								}
								elseif ( isset( $status_single['status'] ) && $status_single['status'] == 200 ) {
									$status['success']++;
								}
								elseif ( isset ( $status_single['message'] ) ) {
									$this->notices .= '<div id="notice" class="notice notice-warning"><p>' . $status_single['message'] . '</p></div>';
								}
								else {
									$this->notices .= '<div id="notice" class="notice notice-warning"><p>An unknown error occurred.</p></div>';
								}

							}
						}

						if ( $status == false ) {
							$action = 'no-api';
						}

						if ( $action == 'edit' ) {
							// Check keywords for missing ones and remove them:
							foreach( $keywords as $keyword ) {
								$found = false;
								foreach ( $_POST['request'] as $keyword_request ) {
									if ( $keyword_request['location_id'] == $keyword['location_id'] ) {
										$found = true;
										continue;
									}
								}
								if ( ! $found ) {
									$status = $api->delete_location( $keyword['location_id'] );
								}
							}
						}
					}
				}
			}
			elseif ( isset( $_GET['_wpnonce'] ) ) {

				if ( wp_verify_nonce( $_GET['_wpnonce'], 'bulk-keywords' ) && isset ( $_GET['action'] ) && $_GET['action'] == 'bulk-delete' ) {

					$api = WP_Keyword_Performance_API::get_instance();

					foreach ($_GET['keyword'] as $keyword) {
						$status = $api->delete_keyword( $keyword );
						$action = 'bulk-delete';
					}
				}

				if ( isset ( $_GET['action'] ) && $_GET['action'] == 'delete' ) {

					check_admin_referer('delete_keyword');

					$api = WP_Keyword_Performance_API::get_instance();

					$status = $api->delete_keyword( $_GET['keyword'] );

					wp_redirect( admin_url( 'admin.php?page=control-center-keyword-performance' ) );
					exit();
				}
			}

			if ( isset ( $_GET['action'] ) && $_GET['action'] == 'export' ) {
				$this->export_to_csv();
			}

			if ( isset ( $status ) ) {
				if ( isset( $status['false'] ) && $status['false'] > 0 ) {
					$this->notices .= '<div id="notice" class="notice notice-warning"><p><strong>No Account Found.</strong> Please <a href="' . $this->new_account_link() . '" target="_blank">create an account</a> to begin adding keywords.</p></div>';
				}

				if ( isset( $status['success'] ) && $status['success'] > 0 ) {

					$option = get_option( 'control_center_keyword_performance' );

					if ( !array_key_exists( 'notice_keyword_fetch_period', $option ) || $option['notice_keyword_fetch_period'] != 1 ) {
						$this->notices .= '<div id="message" class="warning notice notice-success is-dismissible below-h2"><p><strong>SEO Control Center:</strong> Your keywords are being processed - please check back in a few hours to see initial results.</p></div>';
						$option['notice_keyword_fetch_period'] = 1;
						update_option( 'control_center_keyword_performance', $option);
					}

					if ( isset ( $action ) ) {
						switch( $action ) {
							case 'bulk-delete':
								$this->notices .= '<div id="message" class="updated notice notice-success is-dismissible below-h2"><p>Keywords Deleted.</p></div>';
								break;
							case 'add':
								$this->notices .= '<div id="message" class="updated notice notice-success is-dismissible below-h2"><p>Keyword Added.</p></div>';
								break;
							case 'bulk-add':
								$this->notices .= '<div id="message" class="updated notice notice-success is-dismissible below-h2"><p>'. $status['success'] .' Keywords Added.</p></div>';
								break;
							case 'edit':
								$this->notices .= '<div id="message" class="updated notice notice-success is-dismissible below-h2"><p>Keyword Updated.</p></div>';
								break;
						}
					}
				}
			}
		}

	}

	/**
	 * Format Requests Remaining into printable format
	 *
	 * @since  1.0.1
	 * @var    int  $limit The Requests limit
	 * @var    int  $used  The Requests used
	 * @return int  Formateed number of requests remaining
	 */
	public static function format_requests_remaining( $limit, $used ) {
		if ( 0 >= $limit || $used == $limit ) {
			return 0;
		}
		return number_format( ( $limit - $used ) / 30 );
	}

	/**
	 * Export data to csv
	 *
	 * @since 1.0.0
	 */
	public function export_to_csv() {
		header( 'Content-type: text/csv' );
		header( 'Content-disposition: attachment; filename=keyword-performance-' . date('Y-m-d-hns') . '.csv' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array(
			'Keyword',
			'Location',
			'Rank',
			'Yesterday',
			'Change Since Yesterday',
			'7 Days Ago',
			'Change Since 7 Days Ago',
			'30 Days Ago',
			'Change Since 30 Days Ago',
			'90 Days Ago',

			'Average Rank',
			'Volatility',
			'Engine',
			'Page'
		));
		$api = WP_Keyword_Performance_API::get_instance();
		$data = $api->get_keywords();
		foreach( $data['keywords'] as $item ) {
			foreach( $item['serp'] as $location ) {

				// # of ranking comparing for volatility score
				$count = 0;

				// Calculate Mean
				$dates = array( 'rank', 'prev_rank', 'rank7', 'rank30', 'rank90' );
				$mean = 0;
				foreach ( $dates as $date ) {
					if ( !is_null( $location[ $date ] ) ) {
						$mean += $location[ $date ];
						$count++;
					}
				}

				if ( $count > 0 ) {
					$mean = $mean / $count;

					// Determine each period's deviation
					$deviation = 0;

					foreach ( $dates as $date ) {
						$item_deviation = pow( $location[ $date ] - $mean, 2 );
						$deviation += $item_deviation;
					}

					$deviation = $deviation / $count;

					$volatility = number_format( sqrt( $deviation ), 1 );
				}
				else {
					$volatility = '';
				}

				fputcsv( $out, array(
					$item['keyword'],
					$location['region'],
					$location['rank'],
					$location['prev_rank'],
					$location['rank'] - $location['prev_rank'],
					$location['rank7'],
					$location['rank'] - $location['rank7'],
					$location['rank30'],
					$location['rank'] - $location['rank30'],
					$location['rank90'],
					$location['rank'] - $location['rank90'],
					$mean,
					$volatility,
					$location['engine'],
					$location['url']
				));
			}
		}
		fclose($out);
		exit;
	}

	public static $google_locations = array(
		'ps-af' => 'Afghanistan - Persian',
		'fr-dz' => 'Algeria - French',
		'en-as' => 'American Samoa - English',
		'ca-ad' => 'Andorra - Catalan',
		'pt-ao' => 'Angola - Portuguese',
		'en-ai' => 'Anguilla - English',
		'en-ag' => 'Antigua and Barbuda - English',
		'es-ar' => 'Argentina - Spanish',
		'hy-am' => 'Armenia - Armenian',
		'en-au' => 'Australia - English',
		'de-at' => 'Austria - German',
		'az-az' => 'Azerbaijan - Azerbaijani',
		'en-bs' => 'Bahamas - English',
		'ar-bh' => 'Bahrain - Arabic',
		'en-bh' => 'Bahrain - English',
		'bn-bd' => 'Bangladesh - Bengali',
		'be-by' => 'Belarus - Belarusian',
		'nl-be' => 'Belgium - Dutch',
		'en-be' => 'Belgium - English',
		'fr-be' => 'Belgium - French',
		'de-be' => 'Belgium - German',
		'en-bz' => 'Belize - English',
		'fr-bj' => 'Benin - French',
		'es-bo' => 'Bolivia - Spanish',
		'bs-ba' => 'Bosnia and Herzegovina - Bosnian',
		'tn-bw' => 'Botswana - Tswana',
		'pt-br-br' => 'Brazil - Portuguese',
		'pt-br' => 'Brazil - Portuguese',
		'en-vg' => 'British Virgin Islands - English',
		'ms-bn' => 'Brunei - Malay',
		'bg-bg' => 'Bulgaria - Bulgarian',
		'fr-bf' => 'Burkina Faso - French',
		'fr-bi' => 'Burundi - French',
		'km-kh' => 'Cambodia - Khmer',
		'fr-cm' => 'Cameroon - French',
		'en-ca' => 'Canada - English',
		'fr-ca' => 'Canada - French',
		'pt-cv' => 'Cape Verde - Portuguese',
		'fr-cf' => 'Central African Republic - French',
		'fr-td' => 'Chad - French',
		'es-cl' => 'Chile - Spanish',
		'zh-cn-hk' => 'China - Chinese (Simplified)',
		'en-cc' => 'Cocos Islands - English',
		'es-co' => 'Colombia - Spanish',
		'en-ck' => 'Cook Islands - English',
		'es-cr' => 'Costa Rica - Spanish',
		'fr-ci' => 'Cote d\'Ivoire - French',
		'hr-hr' => 'Croatia - Croatian',
		'es-cu' => 'Cuba - Spanish',
		'cs-cz' => 'Czech Republic - Czech',
		'da-dk' => 'Denmark - Danish',
		'fr-dj' => 'Djibouti - French',
		'en-dm' => 'Dominica - English',
		'es-do' => 'Dominican Republic - Spanish',
		'es-ec' => 'Ecuador - Spanish',
		'ar-eg' => 'Egypt - Arabic',
		'en-eg' => 'Egypt - English',
		'es-sp' => 'El Salvador - Spanish',
		'et-ee' => 'Estonia - Estonian',
		'ru-et' => 'Estonia - Russian',
		'am-et' => 'Ethiopia - Amharic',
		'en-fj' => 'Fiji - English',
		'fi-fl' => 'Finland - Finnish',
		'sv-fi' => 'Finland - Swedish',
		'fr-fr' => 'France - French',
		'fr-ga' => 'Gabon - French',
		'en-gm' => 'Gambia - English',
		'ka-ge' => 'Georgia - Kartuli',
		'de-de' => 'Germany - German',
		'en-gh' => 'Ghana - English',
		'en-gi' => 'Gibraltar - English',
		'el-gr' => 'Greece - Greek',
		'da-gl' => 'Greenland - Danish',
		'fr-gp' => 'Guadeloupe - French',
		'es-gt' => 'Guatemala - Spanish',
		'en-gg' => 'Guernsey - English',
		'en-gy' => 'Guyana - English',
		'fr-ht' => 'Haiti - French',
		'es-hn' => 'Honduras - Spanish',
		'zh-hk-hk' => 'Hong Kong - Chinese',
		'en-hk' => 'Hong Kong - English',
		'hu-hu' => 'Hungary - Hungarian',
		'is-is' => 'Iceland - Icelandic',
		'en-in' => 'India - English',
		'hi-in' => 'India - Hindi',
		'en-id' => 'Indonesia - English',
		'id-id' => 'Indonesia - Indonesian',
		'jw-id' => 'Indonesia - Javanese',
		'ar-iq' => 'Iraq - Arabic',
		'en-ie' => 'Ireland - English',
		'ga-ie' => 'Ireland - Irish',
		'en-im' => 'Isle of Man - English',
		'ar-il' => 'Israel - Arabic',
		'en-il' => 'Israel - English',
		'he-il' => 'Israel - Hebrew',
		'it-it' => 'Italy - Italian',
		'en-jm' => 'Jamaica - English',
		'sw-cd' => 'Jamhuri ya Kidemokrasia ya Kongo - Kiswahili',
		'ja-jp' => 'Japan - Japanese',
		'en-je' => 'Jersey - English',
		'ar-jo' => 'Jordan - Arabic',
		'en-jo' => 'Jordan - English',
		'kk-kz' => 'Kazakhstan - Kazakh',
		'ru-kz' => 'Kazakhstan - Russian',
		'en-ke' => 'Kenya - English',
		'sw-ck' => 'Kenya - Swahili',
		'en-ki' => 'Kiribati - English',
		'ko-kr' => 'Korea - Korean',
		'ar-kw' => 'Kuwait - Arabic',
		'ky-kg' => 'Kyrgyzstan - Kyrgyz',
		'lo-la' => 'Laos - Lao',
		'lv-lv' => 'Latvia - Latvian',
		'lt-lv' => 'Latvia - Lithuanian',
		'ru-lv' => 'Latvia - Russian',
		'ar-lb' => 'Lebanon - Arabic',
		'en-lb' => 'Lebanon - English',
		'fr-lb' => 'Lebanon - French',
		'st-ls' => 'Lesotho - Sesotho',
		'ar-ly' => 'Libya - Arabic',
		'de-li' => 'Liechtenstein - German',
		'lt-lt' => 'Lithuania - Lithuanian',
		'de-lu' => 'Luxembourg - German',
		'mk-mk' => 'Macedonia - Macedonian',
		'mg-mg' => 'Madagascar - Malagasy',
		'ny-mw' => 'Malawi - Swahili',
		'en-my' => 'Malaysia - English',
		'ms-my' => 'Malaysia - Malay',
		'en-mv' => 'Maldives - English',
		'fr-ml' => 'Mali - French',
		'en-mt' => 'Malta - English',
		'mt-mt' => 'Malta - Maltese',
		'en-mu' => 'Mauritius - English',
		'es-mx' => 'Mexico - Spanish',
		'en-fm' => 'Micronesia - English',
		'mo-md' => 'Moldova - Moldovan',
		'ru-md' => 'Moldova - Russian',
		'mn-mn' => 'Mongolia - Mongolian',
		'en-ms' => 'Montserrat - English',
		'fr-ma' => 'Morocco - French',
		'pt-mz' => 'Mozambique - Portuguese',
		'en-na' => 'Namibia - English',
		'en-nr' => 'Nauru - English',
		'ne-np' => 'Nepal - Hindi',
		'nl-nl' => 'Netherlands - Dutch',
		'en-nz' => 'New Zealand - English',
		'mi-nz' => 'New Zealand - Maori',
		'es-ni' => 'Nicaragua - Spanish',
		'fr-ne' => 'Niger - French',
		'en-ng' => 'Nigeria - English',
		'ha-ng' => 'Nigeria - Hausa',
		'ig-ng' => 'Nigeria - Igbo',
		'yo-ng' => 'Nigeria - Yoruba',
		'en-nu' => 'Niue - English',
		'en-nf' => 'Norfolk Island - English',
		'no-no' => 'Norway - Norwegian',
		'ar-om' => 'Oman - Arabic',
		'en-om' => 'Oman - English',
		'en-bk' => 'Pakistan - English',
		'ar-ps' => 'Palestine - Arabic',
		'es-pa' => 'Panama - Spanish',
		'es-py' => 'Paraguay - Spanish',
		'es-pe' => 'Peru - Spanish',
		'en-ph' => 'Philippines - English',
		'tl-ph' => 'Philippines - Tagalog',
		'en-pn' => 'Pitcairn Island - English',
		'pl-pl' => 'Poland - Polish',
		'pt-pt' => 'Portugal - Portuguese',
		'en-pr' => 'Puerto Rico - English',
		'es-pr' => 'Puerto Rico - Spanish',
		'ar-qa' => 'Qatar - Arabic',
		'en-qa' => 'Qatar - English',
		'fr-cd' => 'Rep Dem du Congo - French',
		'fr-cg' => 'Republic of the Congo - French',
		'de-ro' => 'Romania - German',
		'hu-ro' => 'Romania - Hungarian',
		'ro-ro' => 'Romania - Romanian',
		'ru-ru' => 'Russia - Russian',
		'en-rw' => 'Rwanda - English',
		'en-sh' => 'Saint Helena - English',
		'en-vc' => 'Saint Vincent and the Grenadines - English',
		'en-ws' => 'Samoa - English',
		'it-sm' => 'San Marino - Italian',
		'pt-st' => 'Sao Tome and Principe - Portuguese',
		'ar-sa' => 'Saudi Arabia - Arabic',
		'en-sa' => 'Saudi Arabia - English',
		'fr-sn' => 'Senegal - French',
		'sr-rs' => 'Serbia - Serbian',
		'cr-sc' => 'Seychelles - Seychellois Creole',
		'en-sl' => 'Sierra Leone - English',
		'zh-cn-sg' => 'Singapore - Chinese',
		'en-sg' => 'Singapore - English',
		'ms-sg' => 'Singapore - Malay',
		'ta-sg' => 'Singapore - Tamil',
		'sk-sk' => 'Slovakia - Slovak',
		'sl-si' => 'Slovenia - Slovenian',
		'en-sb' => 'Solomon Islands - English',
		'so-so' => 'Somalia - Somali',
		'af-za' => 'South Africa - Afrikaans',
		'en-za' => 'South Africa - English',
		'xh-za' => 'South Africa - IsiXhosa',
		'zu-za' => 'South Africa - IsiZulu',
		'ca-ct' => 'Spain - Catalan',
		'es-es' => 'Spain - Spanish',
		'en-lk' => 'Sri Lanka - English',
		'sv-se' => 'Sweden - Swedish',
		'en-ch' => 'Switzerland - English',
		'fr-ch' => 'Switzerland - French',
		'de-ch' => 'Switzerland - German',
		'it-ch' => 'Switzerland - Italian',
		'rm-ch' => 'Switzerland - Rumantsch',
		'zh-cn-tw' => 'Taiwan - Chinese',
		'zh-tw-tw' => 'Taiwan - Chinese Traditional Han',
		'en-tw' => 'Taiwan - English',
		'tg-tj' => 'Tajikistan - Tajik',
		'sw-tz' => 'Tanzania - Swahili',
		'en-th' => 'Thailand - English',
		'th-th' => 'Thailand - Thai',
		'pt-tl' => 'Timor-Leste - Portuguese',
		'fr-tg' => 'Togo - French',
		'en-tk' => 'Tokelau - English',
		'en-to' => 'Tonga - English',
		'en-tt' => 'Trinidad and Tobago',
		'ar-tn' => 'Tunisia - Arabic',
		'fr-tn' => 'Tunisia - French',
		'tr-tr' => 'Turkey - Turkish',
		'tk-tm' => 'Turkmenistan - Turkmen',
		'en-ug' => 'Uganda - English',
		'en-ua' => 'Ukraine - English',
		'ru-ua' => 'Ukraine - Russian',
		'uk-ua' => 'Ukraine - Ukrainian',
		'ar-ae' => 'United Arab Emirates - Arabic',
		'en-ae' => 'United Arab Emirates - English',
		'en-uk' => 'United Kingdom - English',
		'en-us' => 'United States - English',
		'es-us' => 'United States - Spanish',
		'es-uy' => 'Uruguay - Spanish',
		'uz-uz' => 'Uzbekistan - Uzbek',
		'en-vu' => 'Vanuatu - English',
		'es-ve' => 'Venezuela - Spanish',
		'zh-cn-vn' => 'Vietnam - Chinese',
		'en-vn' => 'Vietnam - English',
		'fr-vn' => 'Vietnam - French',
		'vi-vn' => 'Vietnam - Vietnamese',
		'en-vi' => 'Virgin Islands - English',
		'en-zm' => 'Zambia - English',
		'en-zw' => 'Zimbabwe - English',
	);

	public static $yahoo_locations = array(
		'es-ar' => 'Argentina - Spanish',
		'en-au' => 'Australia - English',
		'de-at' => 'Austria - German',
		'nl-be' => 'Belgium - Dutch',
		'pt-br' => 'Brazil - Portuguese',
		'en-ca' => 'Canada - English',
		'fr-ca' => 'Canada - French',
		'es-cl' => 'Chile - Spanish',
		'es-co' => 'Colombia - Spanish',
		'da-dk' => 'Denmark - Danish',
		'fi-fl' => 'Finland - Finnish',
		'fr-fr' => 'France - French',
		'de-de' => 'Germany - German',
		'el-gr' => 'Greece - Greek',
		'zh-hk' => 'Hong Kong - Traditional Chinese',
		'be-in' => 'India - Bengali',
		'en-in' => 'India - English',
		'hi-in' => 'India - Hindi',
		'ka-in' => 'India - Kannada',
		'ml-in' => 'India - Malayalam',
		'mr-in' => 'India - Marathi',
		'ta-in' => 'India - Tamil',
		'te-in' => 'India - Telugu',
		'id-id' => 'Indonesia - Indonesian',
		'en-ie' => 'Ireland - English',
		'ar-il' => 'Israel - Arabic',
		'it-it' => 'Italy - Italian',
		'ja-jp' => 'Japan - Japanese',
		'ko-kr' => 'Korea - Korean',
		'en-il' => 'Maktoob - English',
		'en-my' => 'Malaysia - English',
		'es-mx' => 'Mexico - Spanish',
		'nl-nl' => 'Netherlands - Dutch',
		'en-nz' => 'New Zealand - English',
		'no-no' => 'Norway - Norwegian',
		'es-pe' => 'Peru - Spanish',
		'en-ph' => 'Philippines - English',
		'pl-pl' => 'Poland - Polish',
		'ro-ro' => 'Romania - Romanian',
		'ru-ru' => 'Russia - Russian',
		'en-sg' => 'Singapore - English',
		'en-za' => 'South Africa - English',
		'es-es' => 'Spain - Spanish',
		'sv-se' => 'Sweden - Swedish',
		'nl-ch' => 'Switzerland - Dutch',
		'fr-ch' => 'Switzerland - French',
		'de-ch' => 'Switzerland - German',
		'it-ch' => 'Switzerland - Italian',
		'zh-cn-tw' => 'Taiwan - Traditional Chinese',
		'th-th' => 'Thailand - Thai',
		'tr-tr' => 'Turkey - Turkish',
		'en-uk' => 'United Kingdom - English',
		'en-us' => 'United States - English',
		'es-us' => 'United States - Spanish',
		'es-ve' => 'Venezula - Spanish',
		'vi-vn' => 'Vietnam - Vietnamese',
	);

	public static $bing_locations = array(
		'es-ar' => 'Argentina - Spanish',
		'en-au' => 'Australia - English',
		'de-at' => 'Austria - German',
		'nl-be' => 'Belgium - Dutch',
		'fr-be' => 'Belgium - French',
		'pt-br' => 'Brazil - Protuguese',
		'en-ca' => 'Canada - English',
		'fr-ca' => 'Canada - French',
		'es-cl' => 'Chile - Spanish',
		'da-dk' => 'Denmark - Danish',
		'ar-eg' => 'Egypt',
		'fi-fi' => 'Finland - Finnish',
		'fr-fr' => 'France - French',
		'de-de' => 'Germany - German',
		'zh-hk' => 'Hong Kong S.A.R.',
		'en-in' => 'India - English',
		'en-id' => 'Indonesia - English',
		'en-ie' => 'Ireland - English',
		'it-it' => 'Italy - Italian',
		'ja-jp' => 'Japan - Japanese',
		'ko-kr' => 'Korea - Korean',
		'es-mx' => 'Mexico - Spanish',
		'nl-nl' => 'Netherlands -  Dutch',
		'en-nz' => 'New Zealand - English',
		'nb-no' => 'Norway - Norwegian',
		'zh-cn' => 'People\'s Republic of China',
		'pl-pl' => 'Poland',
		'pt-pt' => 'Portugal - Protuguese',
		'en-ph' => 'Republic of the Philippines - English',
		'ru-ru' => 'Russia - Russian',
		'ar-sa' => 'Saudi Arabia',
		'en-sg' => 'Singapore - English',
		'en-za' => 'South Africa',
		'es-es' => 'Spain - Spanish',
		'sv-se' => 'Sweden - Swedish',
		'fr-ch' => 'Switzerland - French',
		'de-ch' => 'Switzerland - German',
		'zh-tw' => 'Taiwan',
		'tr-tr' => 'Turkey',
		'ar-ae' => 'United Arab Emirates',
		'en-gb' => 'United Kingdom',
		'en-uk' => 'United Kingdom',
		'en-us' => 'United States - English',
		'es-us' => 'United States - Spanish',
	);

	/**
	 * The name of the plugin used to uniquely identify it within the context of WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}

