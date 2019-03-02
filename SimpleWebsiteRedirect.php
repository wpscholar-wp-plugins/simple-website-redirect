<?php
/**
 * Plugin Name: Simple Website Redirect
 * Plugin URI:  https://wpscholar.com/wordpress-plugins/simple-website-redirect/
 * Description: A simple plugin designed to redirect an entire website (except the WordPress admin) to another website.
 * Version:     1.0.2
 * Author:      Micah Wood
 * Author URI:  https://wpscholar.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-website-redirect
 *
 * @package simple-website-redirect
 */

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
	 * Hook our custom functions into WordPress core.
	 */
	public static function initialize() {

		load_plugin_textdomain( 'simple-website-redirect', false, __DIR__ . '/languages' );

		add_action( 'init', array( __CLASS__, 'init' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 99 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'plugin_action_links' ) );
	}

	/**
	 * Primary functionality - handles website redirect based on current configuration.
	 */
	public static function init() {
		global $pagenow;
		// Allow requests to /wp-admin and wp-login so that admins can attempt to login
		if ( 'wp-login.php' !== $pagenow && ! is_admin() ) {
			$redirect_enabled = wp_validate_boolean( get_option( 'simple_website_redirect_status', false ) );
			if ( $redirect_enabled ) {
				$redirect_url = self::sanitize_redirect_url( get_option( 'simple_website_redirect_url' ) );
				if ( ! empty( $redirect_url ) ) {
					$redirect_type = self::sanitize_redirect_type( get_option( 'simple_website_redirect_type' ) );
					wp_safe_redirect( $redirect_url . $_SERVER['REQUEST_URI'], $redirect_type );
					exit;
				}
			}
		}
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
		$scheme    = wp_parse_url( $url, PHP_URL_SCHEME );
		$host      = untrailingslashit( wp_parse_url( $url, PHP_URL_HOST ) );
		if ( $scheme && $host ) {
			$current_host = untrailingslashit( wp_parse_url( home_url(), PHP_URL_HOST ) );
			if ( $host !== $current_host ) {
				$path      = (string) wp_parse_url( $url, PHP_URL_PATH );
				$clean_url = "{$scheme}://{$host}{$path}";
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
		$clean_type = 301;
		if ( 302 === absint( $type ) ) {
			$clean_type = 302;
		}

		return $clean_type;
	}

	/**
	 * Register our settings.
	 */
	public static function admin_init() {

		register_setting( self::PAGE, 'simple_website_redirect_url', array( __CLASS__, 'sanitize_redirect_url' ) );
		register_setting( self::PAGE, 'simple_website_redirect_type', array( __CLASS__, 'sanitize_redirect_type' ) );
		register_setting( self::PAGE, 'simple_website_redirect_status', 'wp_validate_boolean' );

		add_settings_section(
			'settings',
			esc_html__( 'Settings', 'simple-website-redirect' ),
			'__return_null',
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
				'name'    => 'simple_website_redirect_type',
				'options' => array(
					301 => esc_html__( 'Permanent', 'simple-website-redirect' ),
					302 => esc_html__( 'Temporary', 'simple-website-redirect' ),
				),
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
					0 => esc_html__( 'Disabled', 'simple-website-redirect' ),
					1 => esc_html__( 'Enabled', 'simple-website-redirect' ),
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
			'<input type="%s" class="%s" name="%s" value="%s" placeholder="%s" />',
			esc_attr( isset( $args['type'] ) ? $args['type'] : 'text' ),
			esc_attr( isset( $args['class'] ) ? $args['class'] : '' ),
			esc_attr( $name ),
			esc_attr( get_option( $name, '' ) ),
			esc_attr( isset( $args['placeholder'] ) ? $args['placeholder'] : '' )

		);
	}

	/**
	 * Outputs a select field.
	 *
	 * @param array $args Select field properties.
	 */
	public static function select_field( array $args ) {

		$name = isset( $args['name'] ) ? $args['name'] : '';
		$options = isset( $args['options'] ) ? (array) $args['options'] : [];
		$value = get_option( $name, '' );

		$opening_tag = sprintf( '<select name="%s">', esc_attr( $name ) ) . PHP_EOL;

		$option_elements = array();
		foreach ( $options as $option_value => $option_label ) {
			$option_elements[] = sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $option_value ),
				selected( $option_value, $value, false ),
				esc_html( $option_label )
			);
		}

		$closing_tag = '</select>' . PHP_EOL;

		echo $opening_tag . implode( PHP_EOL, $option_elements ) . $closing_tag;
	}

}

SimpleWebsiteRedirect::initialize();
