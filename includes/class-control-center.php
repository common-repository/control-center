<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       www.wetpaint.io
 * @since      1.0.0
 *
 * @package    Control_Center
 * @subpackage Control_Center/includes
 */

/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Control_Center
 * @subpackage Control_Center/includes
 * @author     WetPaint <rc@wetpaint.io>
 */
class Control_Center {

	/**
	 * Instance of this class.
	 *
	 * @since	 2.0.0
	 *
	 * @var		object
	 */
	private static $instance = null;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'control-center';
		$this->version = '1.3.2';

		load_plugin_textdomain( $this->plugin_name );

		$this->setup_defaults();
		$this->define_hooks();

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since	  2.0.0
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
	 * Setup default options
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function setup_defaults() {
		$options = get_option( 'control_center_active_modules' );
		if ( false === $options ) {
			// Enable Keyword Performance Plugin Automatically if no settings
			update_option( 'control_center_active_modules', array( 'keyword_performance' => 1 ) );
		}

		$options = get_option( 'control_center_keyword_performance' );
		if ( false === $options ) {
			// Set default site name if the option isn't set
			update_option( 'control_center_keyword_performance', array( 'site' => get_bloginfo( 'name' ) ) );
		}
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_hooks() {

		add_action( 'admin_init', array( $this, 'enable_disable_modules' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		$options = get_option('control_center_active_modules');
		if ( false != $options ) {
			$options_keys = array_keys( $options );
			foreach ( $options_keys as $option ) {
				if ( 1 == $options[ $option ] ) {
					switch( $option ) {
						case 'keyword_performance':
							require_once plugin_dir_path( dirname( __FILE__ ) ) . 'modules/keyword-performance/keyword-performance.php';
							add_action( 'plugins_loaded', array( 'WP_Keyword_Performance', 'get_instance' ) );
							add_action( 'admin_menu', array( $this, 'add_menu_settings_page' ), 15 );
						break;
					}
				}
			}
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		$screen = get_current_screen();

		if ( strpos( $screen->id, 'control-center' ) !== false) {

			$options = get_option('control_center_keyword_performance');
			if ( isset( $options['api_key'] ) ) {
				$script_params = array(
					'apikey' => $options['api_key']
				);
			}

			wp_localize_script( $this->plugin_name, 'scriptParams', $script_params );

		}
	}

	public function enable_disable_modules() {
		if ( current_user_can( 'manage_options' ) ) {
			if ( isset( $_POST['_wpnonce'] ) ) {
				if ( wp_verify_nonce( $_POST['_wpnonce'], 'deactivate' ) ) {
					$modules[ $_POST['deactivate'] ] = 0;
					update_option('control_center_active_modules', $modules);
					wp_safe_redirect( 'admin.php?page=control-center' );
					exit;
				} elseif ( wp_verify_nonce( $_POST['_wpnonce'], 'activate' ) ) {
					$modules[ $_POST['activate'] ] = 1;
					update_option('control_center_active_modules', $modules);
					wp_safe_redirect( 'admin.php?page=control-center' );
					exit;
				}
			}
		}
	}

	/**
	 * Add Options and Help to the admin Page
	 *
	 * @since 1.1.0
	 */
	public function create_options_help_screen() {
		// Create the WP_Screen object against your admin page handle. This ensures we're working with the right admin page
		$this->control_center_admin = WP_Screen::get( $this->control_center_admin );

		$this->control_center_admin->add_help_tab( array(
			'id'       => 'overview',
			'title'    => __( 'Overview' ),
			'content'  => '<p><em>Need help, or have a suggestion?</em> We\'d love to hear from you!</p><a href="https://www.wetpaintwebtools.com/contact-us/" target="_blank" class="button button-primary button-large">Contact Us</a>'
		));
	}


	public function add_menu_settings_page() {

		// Add Settings sub-page and menu link
		$this->options_screen = add_submenu_page(
			'control-center-keyword-performance',
			'SEO Control Center Settings',
			'Settings',
			'manage_options',
			'control-center-settings',
			array( $this, 'control_center_settings_page' ),
			null
		);

		$options = get_option('control_center_keyword_performance');
		if ( isset( $options['api_key'] ) ) {
			add_action( "load-{$this->options_screen}", array ( &$this, 'create_settings_options_help_screen' ) );
		}
	}

	/**
	 * Add Options and Help to the admin Page
	 *
	 * @since 1.1.0
	 */
	public function create_settings_options_help_screen() {
		// Create the WP_Screen object against your admin page handle. This ensures we're working with the right admin page
		$this->options_screen = WP_Screen::get( $this->options_screen );

		$this->options_screen->add_help_tab( array(
			'id'       => 'overview',
			'title'    => __( 'Overview' ),
			'content'  => '<p><em>Need help, or have a suggestion?</em> We\'d love to hear from you!</p><a href="https://www.wetpaintwebtools.com/contact-us/" target="_blank" class="button button-primary button-large">Contact Us</a>'
		));
	}

	/**
	 * TODO: Add field. Add Comment.
	 *
	 * @since 1.0.0
	 */
	public function control_center_settings_page() {
		include( plugin_dir_path( dirname( __FILE__ ) ) . 'partials/control-center-settings-display.php' );
	}

	public function register_settings() {
		register_setting( $this->plugin_name . '_settings', 'control_center_active_modules' );
		add_settings_section('modules_section', '', array( $this, 'modules_section_text'), 'control_center_modules');
		add_settings_field('keyword_performance', 'Keyword Performance', array( $this, 'keyword_performance_setting_string'), 'control_center_modules', 'modules_section');
	}

	public function modules_section_text() {
		echo '<p>Select the features you\'d like to enable below.</p>';
	}

	public function keyword_performance_setting_string() {
		$options = get_option('control_center_active_modules');
		echo '<input name="control_center_active_modules[keyword_performance]" id="plugin_text_string" type="checkbox" value="1" class="code" ' . checked( 1, $options['keyword_performance'], false ) . ' />';
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		$screen = get_current_screen();

		if ( strpos( $screen->id, 'control-center' ) !== false) {

			wp_enqueue_style( $this->plugin_name, plugin_dir_url( dirname( __FILE__ ) ) . 'css/control-center-admin.css', array(), $this->version, 'all' );

		}

		wp_enqueue_style( $this->plugin_name.'-menu', plugin_dir_url( dirname( __FILE__ ) ) . 'css/control-center-menu.css', array(), $this->version, 'all' );
	}

	/**
	 * Create link to new account
	 *
	 * @since 1.0.0
	 *
	 * @return	string	link to signup page
	 */
	public static function new_account_link() {
		return esc_url( 'https://www.wetpaintwebtools.com/plans/?signupsitename=' . urlencode( get_bloginfo( 'name' )) . '&signupsiteurl=' . urlencode( get_admin_url() ) );
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
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
