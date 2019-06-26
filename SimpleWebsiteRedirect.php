<?php
/**
 * Plugin Name: Simple Website Redirect
 * Plugin URI:  https://wpscholar.com/wordpress-plugins/simple-website-redirect/
 * Description: A simple plugin designed to redirect an entire website (except the WordPress admin) to another website.
 * Version:     1.2.3
 * Author:      Micah Wood
 * Author URI:  https://wpscholar.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-website-redirect
 *
 * @package simple-website-redirect
 */

require __DIR__ . '/Url.php';

use wpscholar\Url;

/**
 * Class SimpleWebsiteRedirect
 */
class SimpleWebsiteRedirect {

	/**
	 * Plugin admin menu page slug.
	 *
	 * @var string
	 */
	const PAGE = 'simple-website-redirect';

	/**
	 * Url instance representing the current URL.
	 *
	 * @var \wpscholar\Url
	 */
	protected static $url;

	/**
	 * Hook our custom functions into WordPress core.
	 */
	public static function initialize() {

		self::$url = new Url();

		load_plugin_textdomain( 'simple-website-redirect', false, __DIR__ . '/languages' );

		add_action( 'init', array( __CLASS__, 'init' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 99 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'plugin_action_links' ) );

		add_filter( 'simple_website_redirect_url', [ __CLASS__, 'filter_redirect_url' ] );
		add_filter( 'simple_website_redirect_should_redirect', [ __CLASS__, 'filter_by_path' ] );
		add_filter( 'simple_website_redirect_should_redirect', [ __CLASS__, 'filter_by_query_params' ] );
		add_filter( 'simple_website_redirect_excluded_paths', [ __CLASS__, 'filter_excluded_paths' ] );
		add_filter( 'simple_website_redirect_excluded_query_params', [ __CLASS__, 'filter_excluded_query_params' ] );

		add_filter( 'allowed_redirect_hosts', array( __CLASS__, 'allowed_redirect_hosts' ) );
	}

	/**
	 * Primary functionality - handles website redirect based on current configuration.
	 */
	public static function init() {
		if ( self::redirects_are_enabled() && self::should_redirect() ) {
			$redirect_url = self::get_redirect_url();
			if ( $redirect_url ) {
				wp_safe_redirect( $redirect_url, self::get_redirect_type() );
				exit;
			}
		}
	}

	/**
	 * Checks if redirects are enabled.
	 *
	 * @return bool
	 */
	public static function redirects_are_enabled() {
		return wp_validate_boolean( get_option( 'simple_website_redirect_status', false ) );
	}

	/**
	 * Check if we should redirect for the current URL.
	 *
	 * @return bool
	 */
	public static function should_redirect() {
		global $pagenow;
		$should_redirect = 'wp-login.php' !== $pagenow && ! is_admin();

		return (bool) apply_filters( 'simple_website_redirect_should_redirect', $should_redirect, self::$url );
	}

	/**
	 * Check if we should preserve URL paths when redirecting.
	 *
	 * @return bool
	 */
	public static function should_preserve_url_paths() {
		return ! filter_var( get_option( 'simple_website_redirect_to_root', false ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Get the redirect type (301 or 302).
	 *
	 * @return int
	 */
	public static function get_redirect_type() {
		return self::sanitize_redirect_type( get_option( 'simple_website_redirect_type' ) );
	}

	/**
	 * Get the redirect URL.
	 *
	 * @return string
	 */
	public static function get_redirect_url() {
		return apply_filters( 'simple_website_redirect_url', self::sanitize_redirect_url( get_option( 'simple_website_redirect_url' ) ) );
	}

	/**
	 * Get a collection of excluded paths.
	 *
	 * @return array
	 */
	public static function get_excluded_paths() {
		return array_filter( explode( ',', get_option( 'simple_website_redirect_exclude_paths', '' ) ) );
	}

	/**
	 * Get a collection of excluded query parameters.
	 *
	 * @return array
	 */
	public static function get_excluded_query_params() {
		return self::parse_query_params( get_option( 'simple_website_redirect_exclude_query_params', '' ) . ',simple-website-redirect' );
	}

	/**
	 * Filter the redirect URL.
	 *
	 * @param string $url The redirect URL.
	 *
	 * @return string
	 */
	public static function filter_redirect_url( $url ) {
		if ( $url && self::should_preserve_url_paths() ) {
			$home_url = new Url( home_url() );
			$find     = trailingslashit( $home_url->path );
			$replace  = '/';
			$pattern  = '#^' . preg_quote( $find, '/' ) . '#';
			$path     = preg_replace( $pattern, $replace, trailingslashit( $_SERVER['REQUEST_URI'] ), 1 );
			$url      = untrailingslashit( $url ) . $path;
		}

		return $url;
	}

	/**
	 * Filter by current path whether or not a redirect should occur.
	 *
	 * @param bool $should_redirect Whether or not a redirect should occur.
	 *
	 * @return bool
	 */
	public static function filter_by_path( $should_redirect ) {
		$excluded_paths = apply_filters( 'simple_website_redirect_excluded_paths', [] );
		foreach ( $excluded_paths as $excluded_path ) {
			if ( 0 === strpos( self::$url->path, $excluded_path ) ) {
				$matches           = 0;
				$excluded_segments = array_filter( explode( '/', $excluded_path ) );
				foreach ( $excluded_segments as $index => $segment ) {
					if ( self::$url->getSegment( $index - 1 ) === $segment ) {
						$matches ++;
					} else {
						break;
					}
				}
				if ( count( $excluded_segments ) === $matches ) {
					$should_redirect = false;
					break;
				}
			}
		}

		return $should_redirect;
	}

	/**
	 * Filter by current path whether or not a redirect should occur.
	 *
	 * @param bool $should_redirect Whether or not a redirect should occur.
	 *
	 * @return bool
	 */
	public static function filter_by_query_params( $should_redirect ) {
		$excluded_params = apply_filters( 'simple_website_redirect_excluded_query_params', [] );
		$query_params    = self::$url->getQueryVars();
		foreach ( $excluded_params as $name => $value ) {
			if ( array_key_exists( $name, $query_params ) ) {
				if ( empty( $value ) ) {
					$should_redirect = false;
					break;
				} elseif ( $query_params[ $name ] === $value ) {
					$should_redirect = false;
					break;
				}
			}
		}

		return $should_redirect;
	}

	/**
	 * A collection of excluded paths that shouldn't result in a redirect.
	 *
	 * @param array $excluded_paths Excluded paths.
	 *
	 * @return array
	 */
	public static function filter_excluded_paths( $excluded_paths ) {
		return array_merge(
			[
				'/admin',
				'/login',
				'/wp-admin',
				'/wp-login.php',
			],
			$excluded_paths,
			self::get_excluded_paths()
		);
	}

	/**
	 * A collection of excluded query parameters that shouldn't result in a redirect.
	 *
	 * @param array $excluded_params Excluded query parameters.
	 *
	 * @return array
	 */
	public static function filter_excluded_query_params( $excluded_params ) {
		return array_merge( $excluded_params, self::get_excluded_query_params() );
	}

	/**
	 * Parse query parameters into an associative array of key/value pairs.
	 *
	 * @param string|array $query_params Query string or array of strings representing key/value pairs.
	 *
	 * @return array
	 */
	public static function parse_query_params( $query_params ) {
		$params = [];
		$pairs  = is_array( $query_params ) ? $query_params : array_filter( explode( ',', $query_params ) );
		foreach ( $pairs as $pair ) {
			$parts           = explode( '=', $pair, 2 );
			$name            = array_shift( $parts );
			$value           = array_shift( $parts );
			$params[ $name ] = $value;
		}

		return $params;
	}

	/**
	 * Sanitize query params
	 *
	 * @param string $value Comma separated list of query parameters.
	 *
	 * @return string
	 */
	public static function sanitize_query_params( $value ) {
		$params = [];
		if ( ! empty( $value ) ) {
			$parsed = self::parse_query_params( $value );
			$clean  = array_combine(
				array_map( [ __CLASS__, 'sanitize_query_param' ], array_keys( $parsed ) ),
				array_map( [ __CLASS__, 'sanitize_query_param' ], array_values( $parsed ) )
			);

			foreach ( $clean as $k => $v ) {
				$params[] = empty( $v ) ? $k : "$k=$v";
			}
		}

		return implode( ',', $params );
	}

	/**
	 * Sanitize a query parameter name or value.
	 *
	 * @param string $param Query parameter name or value.
	 *
	 * @return string
	 */
	public static function sanitize_query_param( $param ) {
		return (string) preg_replace( '/[^0-9a-zA-Z_\-\+\[\]\=\%]/', '', trim( $param ) );
	}

	/**
	 * Sanitize redirect URL
	 *
	 * @param string $url The redirect URL.
	 *
	 * @return string
	 */
	public static function sanitize_redirect_url( $url ) {
		$clean_url = '';

		if ( $url ) {
			$redirect_url = new Url( $url );
			$home_url     = new Url( home_url() );

			$redirect = untrailingslashit( $redirect_url->host . $redirect_url->path );
			$home     = untrailingslashit( $home_url->host . $home_url->path );

			if ( false === strpos( $home, $redirect ) ) {
				$clean_url = "{$redirect_url->scheme}://{$redirect_url->host}{$redirect_url->path}";
			}
		}

		return $clean_url;
	}

	/**
	 * Sanitize redirect type
	 *
	 * @param int $type Redirect type; can be 301 or 302.
	 *
	 * @return int
	 */
	public static function sanitize_redirect_type( $type ) {
		return 302 === absint( $type ) ? 302 : 301;
	}

	/**
	 * Register our settings.
	 */
	public static function admin_init() {

		$settings = [
			'simple_website_redirect_url'                  => [ __CLASS__, 'sanitize_redirect_url' ],
			'simple_website_redirect_type'                 => [ __CLASS__, 'sanitize_redirect_type' ],
			'simple_website_redirect_status'               => 'wp_validate_boolean',
			'simple_website_redirect_to_root'              => 'wp_validate_boolean',
			'simple_website_redirect_exclude_paths'        => 'sanitize_text_field',
			'simple_website_redirect_exclude_query_params' => [ __CLASS__, 'sanitize_query_params' ],
		];

		foreach ( $settings as $option_name => $sanitize_callback ) {
			register_setting( self::PAGE, $option_name, $sanitize_callback );
		}

		add_settings_section(
			'settings',
			esc_html__( 'Settings', 'simple-website-redirect' ),
			'__return_null',
			self::PAGE
		);

		add_settings_section(
			'advanced-settings',
			esc_html__( 'Advanced Settings', 'simple-website-redirect' ),
			function () {
				echo '<p>';
				esc_html_e( 'Use the exclude fields to prevent redirects in certain use cases, such as when using a front-end page builder.', 'simple-website-redirect' );
				echo '</p>';
			},
			self::PAGE
		);

		add_settings_field(
			'simple_website_redirect_url',
			esc_html__( 'Redirect URL', 'simple-website-redirect' ),
			array( __CLASS__, 'input_field' ),
			self::PAGE,
			'settings',
			[
				'name'        => 'simple_website_redirect_url',
				'type'        => 'url',
				'class'       => 'regular-text',
				'placeholder' => 'https://',
			]
		);

		add_settings_field(
			'simple_website_redirect_type',
			esc_html__( 'Redirect Type', 'simple-website-redirect' ),
			array( __CLASS__, 'select_field' ),
			self::PAGE,
			'settings',
			[
				'name'      => 'simple_website_redirect_type',
				'options'   => array(
					301 => __( 'Permanent', 'simple-website-redirect' ),
					302 => __( 'Temporary', 'simple-website-redirect' ),
				),
				'help_text' => __( 'Always set to "Temporary" when testing.', 'simple-website-redirect' ),
			]
		);

		add_settings_field(
			'simple_website_redirect_status',
			esc_html__( 'Redirect Status', 'simple-website-redirect' ),
			array( __CLASS__, 'select_field' ),
			self::PAGE,
			'settings',
			[
				'name'    => 'simple_website_redirect_status',
				'options' => array(
					0 => __( 'Disabled', 'simple-website-redirect' ),
					1 => __( 'Enabled', 'simple-website-redirect' ),
				),
			]
		);

		add_settings_field(
			'simple_website_redirect_exclude_query_params',
			esc_html__( 'Exclude Query Parameters', 'simple-website-redirect' ),
			array( __CLASS__, 'input_field' ),
			self::PAGE,
			'advanced-settings',
			[
				'name'      => 'simple_website_redirect_exclude_query_params',
				'class'     => 'regular-text',
				'help_text' => esc_html__( 'Separate query parameters with commas (e.g. fl_builder, elementor-preview).', 'simple-website-redirect' ),
			]
		);

		add_settings_field(
			'simple_website_redirect_exclude_paths',
			esc_html__( 'Exclude Paths', 'simple-website-redirect' ),
			array( __CLASS__, 'input_field' ),
			self::PAGE,
			'advanced-settings',
			[
				'name'      => 'simple_website_redirect_exclude_paths',
				'class'     => 'regular-text',
				'help_text' => __( 'Separate paths with commas (e.g. /wp-admin,/wp-login.php).', 'simple-website-redirect' ),
			]
		);

		add_settings_field(
			'simple_website_redirect_to_root',
			esc_html__( 'Preserve URL Paths', 'simple-website-redirect' ),
			array( __CLASS__, 'select_field' ),
			self::PAGE,
			'advanced-settings',
			[
				'name'    => 'simple_website_redirect_to_root',
				'options' => array(
					0 => esc_html__( 'Yes (Recommended)', 'simple-website-redirect' ),
					1 => esc_html__( 'No (Redirects all pages to the homepage)', 'simple-website-redirect' ),
				),
			]
		);

	}

	/**
	 * Add our custom admin menu page.
	 */
	public static function admin_menu() {

		add_submenu_page(
			'options-general.php',
			esc_html__( 'Simple Website Redirect', 'simple-website-redirect' ),
			esc_html__( 'Website Redirect', 'simple-website-redirect' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render admin page.
	 */
	public static function render_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
		settings_errors( self::PAGE );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				do_settings_sections( self::PAGE );
				settings_fields( self::PAGE );
				submit_button( esc_html__( 'Save Settings', 'simple-website-redirect' ) );
				?>
			</form>
			<style>
				.wrap form h2:nth-of-type(2) {
					display: none;
				}

				.wrap form h2:nth-of-type(2) + p {
					display: none;
				}

				.wrap form h2:nth-of-type(2) + p + table {
					display: none;
				}
			</style>
			<script>
				jQuery( document ).ready( function ($) {
					var showText = '<?php echo esc_js( __( 'Show Advanced Settings', 'simple-website-redirect' ) ); ?>';
					var hideText = '<?php echo esc_js( __( 'Hide Advanced Settings', 'simple-website-redirect' ) ); ?>';
					var $toggle = $( '<a href="#">' + showText + '</a>' );
					var $heading = $( '.wrap form h2:nth-of-type(2)' );
					var $description = $heading.next();
					var $table = $description.next();
					$table.after( $toggle );

					$toggle.click( function (e) {
						e.preventDefault();
						toggle( !$heading.is( ':visible' ) );
					} );

					function toggle(show) {
						$toggle.text( show ? hideText : showText );
						$heading.toggle( show );
						$description.toggle( show );
						$table.toggle( show );
					}
				} );
			</script>
		</div>
		<?php
	}

	/**
	 * Add settings link to plugin on plugin list in the WP admin.
	 *
	 * @param array $links Existing plugin action links.
	 *
	 * @return array
	 */
	public static function plugin_action_links( $links ) {

		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=' . self::PAGE ) ),
			esc_html__( 'Settings', 'simple-website-redirect' )
		);

		$links[] = $settings_link;

		return $links;
	}

	/**
	 * Outputs an input field.
	 *
	 * @param array $args Input field properties.
	 */
	public static function input_field( array $args ) {

		$name = isset( $args['name'] ) ? $args['name'] : '';

		printf(
			'<input type="%s" class="%s" name="%s" value="%s" placeholder="%s" />%s',
			esc_attr( isset( $args['type'] ) ? $args['type'] : 'text' ),
			esc_attr( isset( $args['class'] ) ? $args['class'] : '' ),
			esc_attr( $name ),
			esc_attr( get_option( $name, '' ) ),
			esc_attr( isset( $args['placeholder'] ) ? $args['placeholder'] : '' ),
			isset( $args['help_text'] ) ? sprintf( '<p class="description">%s</p>', esc_html( $args['help_text'] ) ) : ''
		);
	}

	/**
	 * Outputs a select field.
	 *
	 * @param array $args Select field properties.
	 */
	public static function select_field( array $args ) {

		$name    = isset( $args['name'] ) ? $args['name'] : '';
		$options = isset( $args['options'] ) ? (array) $args['options'] : [];
		$value   = get_option( $name, '' );

		echo sprintf( '<select name="%s">', esc_attr( $name ) ) . PHP_EOL;

		foreach ( $options as $option_value => $option_label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $option_value ),
				selected( $option_value, $value, false ),
				esc_html( $option_label )
			);
			echo PHP_EOL;
		}
		echo '</select>' . PHP_EOL;

		if ( ! empty( $args['help_text'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['help_text'] ) );
		}
	}

	/**
	 * Hook to register the provided URL as an allowed redirect host.
	 *
	 * @param array $hosts Allowed redirect hosts
	 *
	 * @return array
	 */
	public static function allowed_redirect_hosts( $hosts ) {
		$url = get_option( 'simple_website_redirect_url' );
		if ( $url ) {
			$hosts[] = wp_parse_url( $url, PHP_URL_HOST );
		}

		return $hosts;
	}

}

SimpleWebsiteRedirect::initialize();
