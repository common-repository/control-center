<?php

class WP_Keyword_Performance_API {

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @var		object
	 */
	private static $instance = null;

	/**
	 * The site's API Key
	 *
	 * @var string
	 */
	private $api_key = false;

	/**
	 * The site we are connected to
	 *
	 * @var string
	 */
	private $site = false;

	/**
	 * The API URL
	 *
	 * @var string
	 */
	private $api_url = 'https://api.wetpaint.io';

	/**
	 * The API Version
	 *
	 * @var string
	 */
	private $api_version = 'v1';

	/**
	 * Error messages
	 *
	 * @var array
	 */
	private $errors = array();

	/**
	 * Total API calls made per page load
	 * Used for debugging and optimization
	 *
	 * @var array
	 */
	private $count = 0;

	/**
	 * Public constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$options = get_option('control_center_keyword_performance');
		if ( isset( $options['site'] ) ) {
			$this->site = $options['site'];
		}
		if ( isset( $options['api_key'] ) ) {
			$this->api_key = $options['api_key'];
		}
	}

	/**
	 * Set API Keys
	 *
	 * @param	$site	string Site to set
	 * @param	$api_key	string   API Key to set
	 *
	 * @return void
	 */
	public function set_api_keys( $site = false, $api_key = false ) {
		if ( $site !== false ) {
			$this->site = $site;
		}
		if ( $api_key !== false ) {
			$this->api_key = $api_key;
		}
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
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Get a specific keyword.
	 *
	 * @param string A record ID.
	 * @param bool   Allow API calls to be cached.
	 * @param int    Set transient expiration in seconds.
	 *
	 * @return mixed
	 */
	public function get_keyword( $record_id = false, $allow_cache = true, $expiration = 3600 ) {
		if ( false === $record_id ) {
			return false;
		}

		$args = array( 'method' => 'GET' );

		return $this->remote_request( '/keywords/' . $record_id, $args, $allow_cache, $expiration );
	}

	/**
	 * Get the chart for a specific keyword.
	 *
	 * @param string A record ID.
	 * @param bool   Allow API calls to be cached.
	 * @param int    Set transient expiration in seconds.
	 *
	 * @return mixed
	 */
	public function get_chart( $record_id = false, $start_date = false, $end_date = false, $allow_cache = true, $expiration = 3600 ) {
		if ( false === $record_id ) {
			return false;
		}

		$args = array( 'method' => 'GET' );
		$query_args = array();

		if ( $start_date !== false ) {
			$query_args['start_date'] = $start_date;
		}
		if ( $end_date !== false ) {
			$query_args['end_date'] = $end_date;
		}
		$url = '/charts/' . $record_id;
		$query = http_build_query( $query_args, null, '&' );
		if ( ! empty( $query ) ) {
			$url .= '?' . $query;
		}

		return $this->remote_request( $url, $args, $allow_cache, $expiration );
	}

	/**
	 * Get account limits
	 *
	 * @return mixed
	 */
	public function get_account_limits( $allow_cache = true, $expiration = 3600 ) {
		$args = array( 'method' => 'GET' );
		$url = '/account/limits';

		return $this->remote_request( $url, $args, $allow_cache, $expiration );
	}

	/**
	 * Get all keywords.
	 *
	 * @param bool   Allow API calls to be cached.
	 * @param int    Set transient expiration in seconds.
	 *
	 * @return mixed
	 */
	public function get_keywords( $allow_cache = true, $expiration = 3600 ) {
		$args = array( 'method' => 'GET' );

		return $this->remote_request( '/keywords', $args, $allow_cache, $expiration );
	}

	/**
	 * Add keyword.
	 *
	 * @param string $keyword   The keyword to create
	 * @param string $engine   The engine, can be google or bing
	 * @param string $region   The region
	 * @param string $frequency   The frequency
	 * @param string $geo   The geographic zip code. This is only used when engine is google
	 *
	 * @return mixed
	 */
	public function add_keyword( $keyword, $engine, $region, $frequency = 'daily', $geo = '' ) {

		// Clear $geo if not google
		if ( $engine !== 'google' ) {
			$geo = '';
		}

		$args = array( 'method' => 'POST',
							'body' => array(
								'keyword' => $keyword,
								'engine' => $engine,
								'region' => $region,
								'frequency' => $frequency,
								'geo' => $geo
							)
						);

		$this->invalidate_transients();
		return $this->remote_request( '/keywords', $args, false, 0 );
	}

	/**
	 * Delete keyword
	 *
	 * @param int $keyword_id   The id of the keyword
	 *
	 * @return mixed
	 */
	public function delete_keyword( $keyword_id ) {

		$args = array( 'method' => 'DELETE' );

		$this->invalidate_transients();
		return $this->remote_request( '/keywords/' . $keyword_id, $args, false, 0 );
	}

	/**
	 * Update location
	 *
	 * @param int $location_id   The id of the location
	 * @param array $params   The fields to update
	 *
	 * @return mixed
	 */
	public function update_location( $location_id, $params ) {

		$args = array( 'method' => 'PUT',
							'body' => $params
						);

		$this->invalidate_transients();
		return $this->remote_request( '/keywords/locations/' . $location_id, $args, false, 0 );
	}

	/**
	 * Delete location
	 *
	 * @param int $location_id   The id of the location
	 *
	 * @return mixed
	 */
	public function delete_location( $location_id ) {

		$args = array( 'method' => 'DELETE' );

		$this->invalidate_transients();
		return $this->remote_request( '/keywords/locations/' . $location_id, $args, false, 0 );
	}

	/**
	 * Helper function to query the API via wp_remote_request.
	 *
	 * @param string The url to access.
	 * @param string The method of the request.
	 * @param array  The headers sent during the request.
	 * @param bool   Allow API calls to be cached.
	 * @param int    Set transient expiration in seconds.
	 *
	 * @return object The results of the wp_remote_request request.
	 */
	protected function remote_request( $url = '', $args = array(), $allow_cache = true, $expiration = 3600 ) {

		if ( empty( $url ) ) {
			return false;
		}

		if ( empty( $this->site ) || empty( $this->api_key ) ) {
			return false;
		}

		$defaults = array(
			'headers'   => array(),
			'method'    => 'GET',
			'body'      => '',
			'sslverify' => true,
		);

		$this->count++;

		$args = wp_parse_args( $args, $defaults );

		$plugin = WP_Keyword_Performance::get_instance();

		$args['headers']['Authorization'] = 'Basic ' . base64_encode( $this->site . ':' . $this->api_key );
		$args['headers']['User-Agent'] = 'WP/' . get_bloginfo( 'version' ) . '; PHP/' . phpversion() . '; ' . $plugin->get_plugin_name() . '/' . $plugin->get_version() . '; ' . get_site_url();

		$request_url = $this->api_url . '/' . $this->api_version . $url;

		if ( 'GET' === $args['method'] && $allow_cache ) {
			$transient = $this->get_transient( $url );
			if ( false === ( $request = get_transient( $transient ) ) ) {
				$request = wp_remote_request( $request_url, $args );

				set_transient( $transient, $request, $expiration );
			}
		} else {
			$request = wp_remote_request( $request_url, $args );
		}

		if ( ! is_wp_error( $request ) ) {
			$data = apply_filters( 'wp_keyword_performance_api_data', json_decode( $request['body'], true ), $url, $args );

			return $data;
		}
		else {
			// Get error message
			$this->errors['errors']['remote_request_error'] = $request->get_error_message();
		}

		if ( ! empty( $this->errors ) ) {
			delete_transient( $transient );
		}

		return false;
	}

	/**
	 * Helper function to return the transient string based on the url
	 *
	 * @param string  $url The url of the request.
	 *
	 * @return string The transient string
	 */
	private function get_transient( $url ) {
		return 'wp_keyword_performance_' . md5( $url . $this->site . $this->api_key );
	}

	/**
	 * Invalidate transients when data is changed
	 *
	 * @param string  $url The url of the request.
	 *
	 * @return string The transient string
	 */
	public function invalidate_transients() {
		// Invalidate /account/limits transient
		delete_transient( $this->get_transient( '/account/limits' ) );
		// Invalidate /keywords transient
		delete_transient( $this->get_transient( '/keywords' ) );
		if ( isset( $_GET['keyword'] ) ) {
			if ( is_array( $_GET['keyword'] ) ) {
				foreach ($_GET['keyword'] as $keyword) {
					// Invalidate /keywords/:id transient
					delete_transient( $this->get_transient( '/keywords/' . $keyword ) );
				}
			}
			else {
				delete_transient( $this->get_transient( '/keywords/' . $_GET['keyword'] ) );
			}
		}
	}

}
