<?php
/**
 * Settings: admin configuration page and option accessors.
 *
 * @package Gvm_Wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin settings (Settings API) and safe option access.
 *
 * The three required settings are stored as their own options:
 *
 * - gvm_tenant   -> data-gvm-tenant
 * - gvm_secret   -> HMAC-SHA256 key (never rendered on the frontend)
 * - gvm_env_url  -> data-gvm-env (named environment) or data-gvm-endpoint (URL)
 */
class Gvm_Settings {

	const GROUP = 'gvm_settings_group';
	const PAGE  = 'gvm';

	/**
	 * Accepted price range (matches the gvm backend amount limits).
	 *
	 * gvm.js currently still validates `data-gvm-price` as <= 10.00, so prices
	 * above 10 require a matching @wdft/gvm-sdk release.
	 */
	const MIN_PRICE = 0.01;
	const MAX_PRICE = 50.00;

	const OPTION_TENANT          = 'gvm_tenant';
	const OPTION_SECRET          = 'gvm_secret';
	const OPTION_ENV_URL         = 'gvm_env_url';
	const OPTION_CURRENCY        = 'gvm_currency';
	const OPTION_DEFAULT_PRICE   = 'gvm_default_price';
	const OPTION_CALLBACK        = 'gvm_callback';
	const OPTION_POST_TYPES      = 'gvm_post_types';
	const OPTION_TEMPLATE_PAYMENT = 'gvm_template_payment';
	const OPTION_TEMPLATE_PAYWALL = 'gvm_template_paywall';
	const OPTION_TEMPLATE_INLINE  = 'gvm_template_inline';
	const OPTION_TEMPLATE_DOWNLOAD = 'gvm_template_download';
	const OPTION_ANALYTICS        = 'gvm_analytics';
	const OPTION_DEFAULT_HIDE_STRATEGY = 'gvm_default_hide_strategy';

	/**
	 * Named environments understood by gvm.js (data-gvm-env).
	 *
	 * @var string[]
	 */
	private static $envs = array( 'demo', 'dev', 'qa', 'prod', 'local' );

	/**
	 * Option defaults. Never contains a secret value; secrets are entered by the admin.
	 *
	 * @var array
	 */
	private static $defaults = array(
		self::OPTION_TENANT           => '',
		self::OPTION_SECRET           => '',
		self::OPTION_ENV_URL          => 'demo',
		self::OPTION_CURRENCY         => 'PLN',
		self::OPTION_DEFAULT_PRICE    => '0.99',
		self::OPTION_CALLBACK         => '',
		self::OPTION_POST_TYPES       => array( 'post' ),
		self::OPTION_TEMPLATE_PAYMENT => '',
		self::OPTION_TEMPLATE_PAYWALL => '',
		self::OPTION_TEMPLATE_INLINE  => '',
		self::OPTION_TEMPLATE_DOWNLOAD => '',
		self::OPTION_ANALYTICS        => array(),
		self::OPTION_DEFAULT_HIDE_STRATEGY => 'mangle-blur',
	);

	/**
	 * Return the option defaults.
	 *
	 * @return array
	 */
	public static function defaults() {
		return self::$defaults;
	}

	/**
	 * Return a single option with default fallback.
	 *
	 * @param string $key Option key.
	 * @return mixed
	 */
	public static function get( $key ) {
		$default = isset( self::$defaults[ $key ] ) ? self::$defaults[ $key ] : '';

		return get_option( $key, $default );
	}

	/**
	 * Tenant identifier.
	 *
	 * @return string
	 */
	public static function tenant() {
		return (string) self::get( self::OPTION_TENANT );
	}

	/**
	 * HMAC secret. Never rendered to the frontend.
	 *
	 * @return string
	 */
	public static function secret() {
		return (string) self::get( self::OPTION_SECRET );
	}

	/**
	 * Currency code (currently only PLN).
	 *
	 * @return string
	 */
	public static function currency() {
		return (string) self::get( self::OPTION_CURRENCY );
	}

	/**
	 * Default price.
	 *
	 * @return float
	 */
	public static function default_price() {
		return (float) self::get( self::OPTION_DEFAULT_PRICE );
	}

	/**
	 * Valid currencies. `PLN` is the only one supported by gvm.js today; the
	 * allow-list keeps the select ready for future currencies.
	 *
	 * @return array<string,string>
	 */
	public static function currencies() {
		$currencies = array(
			'PLN' => __( 'PLN — Polish złoty', 'gvm-wp' ),
		);

		/**
		 * Filter the selectable currencies (code => label).
		 *
		 * @param array<string,string> $currencies Currency codes and labels.
		 */
		return (array) apply_filters( 'gvm_currencies', $currencies );
	}

	/**
	 * Valid hide strategies.
	 *
	 * @return string[]
	 */
	public static function hide_strategies() {
		return array( 'none', 'blur', 'hide', 'mangle-blur' );
	}

	/**
	 * Global default hide strategy used by new articles and blocks.
	 *
	 * @return string
	 */
	public static function default_hide_strategy() {
		$strategy = (string) self::get( self::OPTION_DEFAULT_HIDE_STRATEGY );

		return in_array( $strategy, self::hide_strategies(), true ) ? $strategy : 'mangle-blur';
	}

	/**
	 * Paywall template slug (single, bundled template).
	 *
	 * @return string
	 */
	public static function default_template() {
		return 'paywall';
	}

	/**
	 * JS callback name used as data-gvm-callback.
	 *
	 * @return string
	 */
	public static function callback() {
		return (string) self::get( self::OPTION_CALLBACK );
	}

	/**
	 * Raw payment template HTML from options (empty = fall back to bundled file).
	 *
	 * @return string
	 */
	public static function template_payment() {
		return (string) self::get( self::OPTION_TEMPLATE_PAYMENT );
	}

	/**
	 * Raw paywall template HTML from options (empty = fall back to bundled file).
	 *
	 * @return string
	 */
	public static function template_paywall() {
		return (string) self::get( self::OPTION_TEMPLATE_PAYWALL );
	}

	/**
	 * Raw inline (block) template HTML from options (empty = bundled file).
	 *
	 * @return string
	 */
	public static function template_inline() {
		return (string) self::get( self::OPTION_TEMPLATE_INLINE );
	}

	/**
	 * Raw download template HTML from options (empty = fall back to bundled file).
	 *
	 * @return string
	 */
	public static function template_download() {
		return (string) self::get( self::OPTION_TEMPLATE_DOWNLOAD );
	}

	/**
	 * Valid analytics tracker tokens (data-gvm-analytics).
	 *
	 * @return string[]
	 */
	public static function analytics_trackers() {
		return array( 'dl', 'gtag', 'custom' );
	}

	/**
	 * Enabled analytics trackers.
	 *
	 * @return string[]
	 */
	public static function analytics() {
		$trackers = array_filter( (array) self::get( self::OPTION_ANALYTICS ) );

		return array_values( array_intersect( $trackers, self::analytics_trackers() ) );
	}

	/**
	 * Named environment (demo|local|prod|qa|dev) or empty string when a custom URL is set.
	 *
	 * @return string
	 */
	public static function env() {
		$value = (string) self::get( self::OPTION_ENV_URL );

		return in_array( $value, self::$envs, true ) ? $value : '';
	}

	/**
	 * Custom endpoint URL when the environment is not a named environment.
	 *
	 * @return string
	 */
	public static function endpoint() {
		$value = (string) self::get( self::OPTION_ENV_URL );

		if ( in_array( $value, self::$envs, true ) || '' === $value ) {
			return '';
		}

		return $value;
	}

	/**
	 * Enabled post types (validated against public types).
	 *
	 * @return string[]
	 */
	public static function post_types() {
		$types     = array_filter( (array) self::get( self::OPTION_POST_TYPES ) );
		$available = get_post_types( array( 'public' => true ), 'names' );
		$types     = array_intersect( (array) $types, array_keys( $available ) );

		return empty( $types ) ? array( 'post' ) : array_values( $types );
	}

	/**
	 * gvm.js module URL. Loaded dynamically from esm.sh by default, filterable for self-hosting.
	 *
	 * If assets/gvm.js has been vendored (build.sh vendor) and no filter overrode the
	 * default, the local copy is used instead.
	 *
	 * @return string
	 */
	public static function sdk_url() {
		$default = 'https://esm.sh/@wdft/gvm-sdk@latest/gvm.js';
		$url     = apply_filters( 'gvm_sdk_url', $default );

		if ( $url === $default && file_exists( GVM_WP_DIR . 'assets/gvm.js' ) ) {
			return GVM_WP_URL . 'assets/gvm.js';
		}

		return $url;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Store defaults on activation (add_option is a no-op when the option exists).
	 *
	 * @return void
	 */
	public static function install_defaults() {
		foreach ( self::$defaults as $key => $value ) {
			add_option( $key, $value );
		}
	}

	/**
	 * Register the options page.
	 *
	 * @return void
	 */
	public static function admin_menu() {
		add_options_page(
			__( 'GVM (GetViaMsg)', 'gvm-wp' ),
			__( 'GetViaMsg', 'gvm-wp' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register settings, section and fields.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			self::GROUP,
			self::OPTION_TENANT,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_tenant' ),
				'default'           => self::$defaults[ self::OPTION_TENANT ],
			)
		);

		register_setting(
			self::GROUP,
			self::OPTION_SECRET,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_secret' ),
				'default'           => self::$defaults[ self::OPTION_SECRET ],
			)
		);

		register_setting(
			self::GROUP,
			self::OPTION_ENV_URL,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_env_url' ),
				'default'           => self::$defaults[ self::OPTION_ENV_URL ],
			)
		);

		register_setting(
			self::GROUP,
			self::OPTION_CURRENCY,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_currency' ),
				'default'           => self::$defaults[ self::OPTION_CURRENCY ],
			)
		);

		register_setting(
			self::GROUP,
			self::OPTION_DEFAULT_PRICE,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_price' ),
				'default'           => self::$defaults[ self::OPTION_DEFAULT_PRICE ],
			)
		);

		register_setting(
			self::GROUP,
			self::OPTION_CALLBACK,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_callback' ),
				'default'           => self::$defaults[ self::OPTION_CALLBACK ],
			)
		);

		register_setting(
			self::GROUP,
			self::OPTION_POST_TYPES,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_post_types' ),
				'default'           => self::$defaults[ self::OPTION_POST_TYPES ],
			)
		);

		register_setting(
			self::GROUP,
			self::OPTION_TEMPLATE_PAYMENT,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_template_html' ),
				'default'           => self::$defaults[ self::OPTION_TEMPLATE_PAYMENT ],
			)
		);

		register_setting(
			self::GROUP,
			self::OPTION_TEMPLATE_PAYWALL,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_template_html' ),
				'default'           => self::$defaults[ self::OPTION_TEMPLATE_PAYWALL ],
			)
		);

		register_setting(
			self::GROUP,
			self::OPTION_TEMPLATE_INLINE,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_template_html' ),
				'default'           => self::$defaults[ self::OPTION_TEMPLATE_INLINE ],
			)
		);

		register_setting(
			self::GROUP,
			self::OPTION_TEMPLATE_DOWNLOAD,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_template_html' ),
				'default'           => self::$defaults[ self::OPTION_TEMPLATE_DOWNLOAD ],
			)
		);

		register_setting(
			self::GROUP,
			self::OPTION_ANALYTICS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_analytics' ),
				'default'           => self::$defaults[ self::OPTION_ANALYTICS ],
			)
		);

		register_setting(
			self::GROUP,
			self::OPTION_DEFAULT_HIDE_STRATEGY,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_hide_strategy' ),
				'default'           => self::$defaults[ self::OPTION_DEFAULT_HIDE_STRATEGY ],
			)
		);

		add_settings_section(
			'gvm_main',
			__( 'GetViaMsg configuration', 'gvm-wp' ),
			array( __CLASS__, 'section_main' ),
			self::PAGE
		);

		add_settings_field( 'gvm_tenant', __( 'Tenant', 'gvm-wp' ), array( __CLASS__, 'field_tenant' ), self::PAGE, 'gvm_main' );
		add_settings_field( 'gvm_secret', __( 'Secret', 'gvm-wp' ), array( __CLASS__, 'field_secret' ), self::PAGE, 'gvm_main' );
		add_settings_field( 'gvm_env_url', __( 'Environment / API URL', 'gvm-wp' ), array( __CLASS__, 'field_env_url' ), self::PAGE, 'gvm_main' );
		add_settings_field( 'gvm_currency', __( 'Currency', 'gvm-wp' ), array( __CLASS__, 'field_currency' ), self::PAGE, 'gvm_main' );
		add_settings_field( 'gvm_default_price', __( 'Default price', 'gvm-wp' ), array( __CLASS__, 'field_default_price' ), self::PAGE, 'gvm_main' );
		add_settings_field( 'gvm_callback', __( 'JS callback', 'gvm-wp' ), array( __CLASS__, 'field_callback' ), self::PAGE, 'gvm_main' );
		add_settings_field( 'gvm_post_types', __( 'Post types', 'gvm-wp' ), array( __CLASS__, 'field_post_types' ), self::PAGE, 'gvm_main' );
		add_settings_field( 'gvm_analytics', __( 'Analytics', 'gvm-wp' ), array( __CLASS__, 'field_analytics' ), self::PAGE, 'gvm_main' );
		add_settings_field( 'gvm_default_hide_strategy', __( 'Default hide strategy', 'gvm-wp' ), array( __CLASS__, 'field_default_hide_strategy' ), self::PAGE, 'gvm_main' );
		add_settings_field( 'gvm_template_payment', __( 'Payment template', 'gvm-wp' ), array( __CLASS__, 'field_template_payment' ), self::PAGE, 'gvm_main' );
		add_settings_field( 'gvm_template_paywall', __( 'Paywall template', 'gvm-wp' ), array( __CLASS__, 'field_template_paywall' ), self::PAGE, 'gvm_main' );
		add_settings_field( 'gvm_template_inline', __( 'Inline template', 'gvm-wp' ), array( __CLASS__, 'field_template_inline' ), self::PAGE, 'gvm_main' );
		add_settings_field( 'gvm_template_download', __( 'Download template', 'gvm-wp' ), array( __CLASS__, 'field_template_download' ), self::PAGE, 'gvm_main' );
	}

	/**
	 * Section description.
	 *
	 * @return void
	 */
	public static function section_main() {
		echo '<p>' . esc_html__( 'These values are rendered as data-gvm-* attributes on the body tag when a paywall is present.', 'gvm-wp' ) . '</p>';
		echo '<p>' . esc_html__( 'Per-article controls (price, hide strategy, redirect, category, condition) appear on every enabled post type below: a "GetViaMsg Paywall" meta box in the classic editor, and a sidebar panel in the block editor. Files are sold through the "Paid download" block or the [gvm-download] shortcode.', 'gvm-wp' ) . '</p>';
		printf(
			'<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></p>',
			esc_html__( 'Full documentation:', 'gvm-wp' ),
			esc_url( 'https://docs.wdft.ovh/getviamsg-wordpress-plugin' ),
			esc_html__( 'docs.wdft.ovh/getviamsg-wordpress-plugin', 'gvm-wp' )
		);
	}

	/**
	 * Sanitize the tenant.
	 *
	 * @param mixed $value Raw input.
	 * @return string
	 */
	public static function sanitize_tenant( $value ) {
		return sanitize_text_field( wp_unslash( $value ) );
	}

	/**
	 * Sanitize the secret. Stored verbatim (trimmed) so the HMAC key is never mangled.
	 *
	 * @param mixed $value Raw input.
	 * @return string
	 */
	public static function sanitize_secret( $value ) {
		return trim( (string) wp_unslash( $value ) );
	}

	/**
	 * Sanitize the environment (named environment only).
	 *
	 * @param mixed $value Raw input.
	 * @return string
	 */
	public static function sanitize_env_url( $value ) {
		$value = trim( (string) wp_unslash( $value ) );

		if ( in_array( $value, self::$envs, true ) ) {
			return $value;
		}

		return 'demo';
	}

	/**
	 * Sanitize the currency (must be in the allow-list).
	 *
	 * @param mixed $value Raw input.
	 * @return string
	 */
	public static function sanitize_currency( $value ) {
		$currency = strtoupper( sanitize_text_field( wp_unslash( $value ) ) );
		$allowed  = array_keys( self::currencies() );

		return in_array( $currency, $allowed, true ) ? $currency : 'PLN';
	}

	/**
	 * Sanitize the default hide strategy.
	 *
	 * @param mixed $value Raw input.
	 * @return string
	 */
	public static function sanitize_hide_strategy( $value ) {
		$strategy = sanitize_text_field( wp_unslash( $value ) );

		return in_array( $strategy, self::hide_strategies(), true ) ? $strategy : 'mangle-blur';
	}

	/**
	 * Sanitize the default price (MIN_PRICE-MAX_PRICE).
	 *
	 * @param mixed $value Raw input.
	 * @return string
	 */
	public static function sanitize_price( $value ) {
		$price = (float) wp_unslash( $value );

		return (string) max( self::MIN_PRICE, min( self::MAX_PRICE, $price ) );
	}

	/**
	 * Sanitize the JS callback name (global function identifier).
	 *
	 * @param mixed $value Raw input.
	 * @return string
	 */
	public static function sanitize_callback( $value ) {
		$callback = sanitize_text_field( wp_unslash( $value ) );

		return preg_replace( '/[^A-Za-z0-9_$.]/', '', (string) $callback );
	}

	/**
	 * Sanitize the enabled post types (multi-select).
	 *
	 * @param mixed $value Raw input.
	 * @return string[]
	 */
	public static function sanitize_post_types( $value ) {
		$types     = is_array( $value ) ? $value : array();
		$types     = array_map( 'sanitize_key', $types );
		$available = get_post_types( array( 'public' => true ), 'names' );
		$types     = array_intersect( $types, array_keys( $available ) );

		return empty( $types ) ? array( 'post' ) : array_values( $types );
	}

	/**
	 * Sanitize the analytics trackers (multi-select).
	 *
	 * @param mixed $value Raw input.
	 * @return string[]
	 */
	public static function sanitize_analytics( $value ) {
		$trackers = is_array( $value ) ? $value : array();
		$trackers = array_map( 'sanitize_key', $trackers );

		return array_values( array_intersect( $trackers, self::analytics_trackers() ) );
	}

	/**
	 * Sanitize a template (raw HTML). Only manageable by admins (manage_options);
	 * stored verbatim so <style>, <template> and data-* markup survive.
	 *
	 * @param mixed $value Raw input.
	 * @return string
	 */
	public static function sanitize_template_html( $value ) {
		return trim( (string) wp_unslash( $value ) );
	}

	/**
	 * Tenant field.
	 *
	 * @return void
	 */
	public static function field_tenant() {
		printf(
			'<input type="text" name="%1$s" value="%2$s" class="regular-text" />',
			esc_attr( self::OPTION_TENANT ),
			esc_attr( self::tenant() )
		);
		echo '<p class="description">' . esc_html__( 'Maps to data-gvm-tenant (3-60 chars).', 'gvm-wp' ) . '</p>';
	}

	/**
	 * Secret field.
	 *
	 * @return void
	 */
	public static function field_secret() {
		printf(
			'<input type="password" name="%1$s" value="%2$s" class="regular-text" autocomplete="new-password" />',
			esc_attr( self::OPTION_SECRET ),
			esc_attr( self::secret() )
		);
		echo '<p class="description">' . esc_html__( 'HMAC-SHA256 key for server-side gvm-signature verification. Stored in options only, never rendered on the frontend.', 'gvm-wp' ) . '</p>';
	}

	/**
	 * Environment / API URL field.
	 *
	 * @return void
	 */
	public static function field_env_url() {
		$current = (string) self::get( self::OPTION_ENV_URL );

		echo '<select name="' . esc_attr( self::OPTION_ENV_URL ) . '">';
		foreach ( self::$envs as $env ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $env ),
				selected( $current, $env, false ),
				esc_html( $env )
			);
		}
		echo '</select>';

		echo '<p class="description">' . esc_html__( 'Named environment, mapped to data-gvm-env.', 'gvm-wp' ) . '</p>';
	}

	/**
	 * Currency field.
	 *
	 * @return void
	 */
	public static function field_currency() {
		$current = self::currency();

		echo '<select name="' . esc_attr( self::OPTION_CURRENCY ) . '">';
		foreach ( self::currencies() as $code => $label ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $code ),
				selected( $current, $code, false ),
				esc_html( $label )
			);
		}
		echo '</select>';

		echo '<p class="description">' . esc_html__( 'Maps to data-gvm-currency. Only PLN is supported by gvm.js today; the list is ready for future currencies.', 'gvm-wp' ) . '</p>';
	}

	/**
	 * Default hide strategy field.
	 *
	 * @return void
	 */
	public static function field_default_hide_strategy() {
		$current = self::default_hide_strategy();
		$labels  = array(
			'none'        => __( 'None', 'gvm-wp' ),
			'hide'        => __( 'Hide', 'gvm-wp' ),
			'blur'        => __( 'Blur', 'gvm-wp' ),
			'mangle-blur' => __( 'Mangle blur', 'gvm-wp' ),
		);

		echo '<select name="' . esc_attr( self::OPTION_DEFAULT_HIDE_STRATEGY ) . '">';
		foreach ( self::hide_strategies() as $strategy ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $strategy ),
				selected( $current, $strategy, false ),
				esc_html( $labels[ $strategy ] )
			);
		}
		echo '</select>';

		echo '<p class="description">' . esc_html__( 'Used for new articles and blocks until overridden per article.', 'gvm-wp' ) . '</p>';
	}

	/**
	 * Default price field.
	 *
	 * @return void
	 */
	public static function field_default_price() {
		printf(
			'<input type="number" step="0.01" min="%3$s" max="%4$s" name="%1$s" value="%2$s" class="small-text" />',
			esc_attr( self::OPTION_DEFAULT_PRICE ),
			esc_attr( (string) self::default_price() ),
			esc_attr( (string) self::MIN_PRICE ),
			esc_attr( (string) self::MAX_PRICE )
		);
		echo '<p class="description">' . esc_html( sprintf( /* translators: 1: min price, 2: max price */ __( 'Maps to data-gvm-price (%1$s-%2$s).', 'gvm-wp' ), self::MIN_PRICE, self::MAX_PRICE ) ) . '</p>';
	}

	/**
	 * JS callback field.
	 *
	 * @return void
	 */
	public static function field_callback() {
		printf(
			'<input type="text" name="%1$s" value="%2$s" class="regular-text" placeholder="window.gvmCallback" />',
			esc_attr( self::OPTION_CALLBACK ),
			esc_attr( self::callback() )
		);
		echo '<p class="description">' . esc_html__( 'Optional global JS function name used as data-gvm-callback.', 'gvm-wp' ) . '</p>';
	}

	/**
	 * Post types field.
	 *
	 * @return void
	 */
	public static function field_post_types() {
		$selected = self::post_types();
		$types    = get_post_types( array( 'public' => true ), 'objects' );

		foreach ( $types as $type ) {
			printf(
				'<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="%1$s[]" value="%2$s" %3$s /> %4$s</label>',
				esc_attr( self::OPTION_POST_TYPES ),
				esc_attr( $type->name ),
				checked( in_array( $type->name, $selected, true ), true, false ),
				esc_html( $type->labels->singular_name )
			);
		}
	}

	/**
	 * Analytics trackers field.
	 *
	 * @return void
	 */
	public static function field_analytics() {
		$selected = self::analytics();
		$labels   = array(
			'dl'     => __( 'Google Tag Manager (dataLayer)', 'gvm-wp' ),
			'gtag'   => __( 'Google Analytics 4 (gtag)', 'gvm-wp' ),
			'custom' => __( 'Custom event (CustomEvent)', 'gvm-wp' ),
		);

		foreach ( self::analytics_trackers() as $tracker ) {
			printf(
				'<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="%1$s[]" value="%2$s" %3$s /> %4$s</label>',
				esc_attr( self::OPTION_ANALYTICS ),
				esc_attr( $tracker ),
				checked( in_array( $tracker, $selected, true ), true, false ),
				esc_html( isset( $labels[ $tracker ] ) ? $labels[ $tracker ] : $tracker )
			);
		}

		echo '<p class="description">' . esc_html__( 'Emits gvm.js analytics events (data-gvm-analytics) to the selected trackers. Leave empty to disable.', 'gvm-wp' ) . '</p>';
		printf(
			'<p class="description">%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></p>',
			esc_html__( 'Event reference:', 'gvm-wp' ),
			esc_url( 'https://docs.wdft.ovh/getviamsg-analytics-events-gvmjs' ),
			esc_html__( 'docs.wdft.ovh/getviamsg-analytics-events-gvmjs', 'gvm-wp' )
		);
	}

	/**
	 * Payment template field (QR / transaction modal).
	 *
	 * @return void
	 */
	public static function field_template_payment() {
		$current = Gvm_Render::template_html( 'payment' );

		printf(
			'<textarea name="%1$s" rows="12" class="large-text code">%2$s</textarea>',
			esc_attr( self::OPTION_TEMPLATE_PAYMENT ),
			esc_textarea( $current )
		);
		echo '<p class="description">' . esc_html__( 'Payment modal <template> inner HTML (QR + send SMS + timer). Uses data-gvm-bind-* elements. Leave empty to use the bundled template.', 'gvm-wp' ) . '</p>';
		self::template_gallery_hint();
	}

	/**
	 * Paywall template field (article blocker).
	 *
	 * @return void
	 */
	public static function field_template_paywall() {
		$current = Gvm_Render::template_html( 'paywall' );

		printf(
			'<textarea name="%1$s" rows="12" class="large-text code">%2$s</textarea>',
			esc_attr( self::OPTION_TEMPLATE_PAYWALL ),
			esc_textarea( $current )
		);
		echo '<p class="description">' . esc_html__( 'Paywall (article blocker) <template> inner HTML, used for page/post paywalls. Uses data-gvm-bind-* elements. Leave empty to use the bundled template.', 'gvm-wp' ) . '</p>';
		self::template_gallery_hint();
	}

	/**
	 * Inline template field (block-level paywall).
	 *
	 * @return void
	 */
	public static function field_template_inline() {
		$current = Gvm_Render::template_html( 'inline' );

		printf(
			'<textarea name="%1$s" rows="12" class="large-text code">%2$s</textarea>',
			esc_attr( self::OPTION_TEMPLATE_INLINE ),
			esc_textarea( $current )
		);
		echo '<p class="description">' . esc_html__( 'Inline paywall used by the "Paid content" block. Keep it compact — it renders inside the content flow. Leave empty to use the bundled template.', 'gvm-wp' ) . '</p>';
		self::template_gallery_hint();
	}

	/**
	 * Download template field (gated file trigger).
	 *
	 * @return void
	 */
	public static function field_template_download() {
		$current = Gvm_Render::template_html( 'download' );

		printf(
			'<textarea name="%1$s" rows="12" class="large-text code">%2$s</textarea>',
			esc_attr( self::OPTION_TEMPLATE_DOWNLOAD ),
			esc_textarea( $current )
		);
		echo '<p class="description">' . esc_html__( 'Download trigger <template> inner HTML (button to buy the gated file). Uses data-gvm-bind-* elements. Leave empty to use the bundled template.', 'gvm-wp' ) . '</p>';
		self::template_gallery_hint();
	}

	/**
	 * Print the template gallery hint with a link to the templates site.
	 *
	 * @return void
	 */
	private static function template_gallery_hint() {
		printf(
			'<p class="description">%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a> · <a href="%4$s" target="_blank" rel="noopener noreferrer">%5$s</a></p>',
			esc_html__( 'Looking for inspiration?', 'gvm-wp' ),
			esc_url( 'https://templates.getviamsg.wdft.ovh/' ),
			esc_html__( 'Browse ready-made GetViaMsg templates →', 'gvm-wp' ),
			esc_url( 'https://docs.wdft.ovh/gvm-js.html' ),
			esc_html__( 'gvm.js template reference', 'gvm-wp' )
		);
	}

	/**
	 * Render the options page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
