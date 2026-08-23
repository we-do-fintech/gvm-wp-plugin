<?php
/**
 * Post meta: per-article paywall configuration.
 *
 * @package Gvm_Wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central place for post meta keys, registration and per-post config lookup.
 */
class Gvm_Post {

	const ENABLED       = '_gvm_enabled';
	const PRICE         = '_gvm_price';
	const HIDE_STRATEGY = '_gvm_hide_strategy';
	const HIDE_PERCENT  = '_gvm_hide_percent';
	const HIDE_SECTIONS = '_gvm_hide_sections';
	const HIDE_WORDS    = '_gvm_hide_words';
	const REFERENCE     = '_gvm_reference';
	const COND          = '_gvm_cond';
	const REDIRECT      = '_gvm_redirect';
	const DOWNLOAD      = '_gvm_download';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
	}

	/**
	 * Meta key => schema used both for register_post_meta and defaults.
	 *
	 * @return array
	 */
	public static function meta_schema() {
		return array(
			self::ENABLED       => array(
				'type'    => 'boolean',
				'default' => false,
			),
			self::PRICE         => array(
				'type'    => 'string',
				'default' => '',
			),
			self::HIDE_STRATEGY => array(
				'type'    => 'string',
				'default' => 'hide',
			),
			self::HIDE_PERCENT  => array(
				'type'    => 'integer',
				'default' => 0,
			),
			self::HIDE_SECTIONS => array(
				'type'    => 'integer',
				'default' => 6,
			),
			self::HIDE_WORDS    => array(
				'type'    => 'integer',
				'default' => 0,
			),
			self::REFERENCE     => array(
				'type'    => 'string',
				'default' => '',
			),
			self::COND          => array(
				'type'    => 'string',
				'default' => '',
			),
			self::REDIRECT      => array(
				'type'    => 'boolean',
				'default' => false,
			),
			self::DOWNLOAD      => array(
				'type'    => 'string',
				'default' => '',
			),
		);
	}

	/**
	 * Register all post meta for REST so the block editor can read/write them.
	 *
	 * @return void
	 */
	public static function register_meta() {
		$schema = self::meta_schema();

		foreach ( Gvm_Settings::post_types() as $post_type ) {
			foreach ( $schema as $key => $meta ) {
				register_post_meta(
					$post_type,
					$key,
					array(
						'type'              => $meta['type'],
						'default'           => $meta['default'],
						'single'            => true,
						'show_in_rest'      => true,
						'sanitize_callback' => array( __CLASS__, 'sanitize_meta' ),
						'auth_callback'     => function () {
							return current_user_can( 'edit_posts' );
						},
					)
				);
			}
		}
	}

	/**
	 * Sanitize a meta value based on its key.
	 *
	 * @param mixed  $value     Raw value.
	 * @param string $meta_key  Meta key.
	 * @param string $meta_type Meta type (unused).
	 * @return mixed
	 */
	public static function sanitize_meta( $value, $meta_key, $meta_type ) {
		switch ( $meta_key ) {
			case self::ENABLED:
				return (bool) $value;
			case self::PRICE:
				$price = (float) $value;

				return $price <= 0 ? '' : max( 0.01, min( 10, $price ) );
			case self::HIDE_STRATEGY:
				$strategy = (string) $value;

				return in_array( $strategy, array( 'none', 'blur', 'hide', 'mangle-blur' ), true ) ? $strategy : 'hide';
			case self::HIDE_PERCENT:
				return max( 0, min( 100, (int) $value ) );
			case self::HIDE_SECTIONS:
				return max( 0, (int) $value );
			case self::HIDE_WORDS:
				return max( 0, (int) $value );
			case self::REFERENCE:
				return self::sanitize_reference( $value );
			case self::COND:
				return self::sanitize_cond( $value );
			case self::REDIRECT:
				return (bool) $value;
			case self::DOWNLOAD:
				return sanitize_file_name( (string) $value );
		}

		return $value;
	}

	/**
	 * Sanitize a gvm condition (data-gvm-cond). Strips HTML tags while
	 * preserving the DSL operators (>, <, quotes, parentheses).
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_cond( $value ) {
		$cond = wp_kses( (string) $value, array() );

		return trim( $cond );
	}

	/**
	 * Sanitize a reference (slug-like, 3-60 chars).
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_reference( $value ) {
		$reference = preg_replace( '/[^A-Za-z0-9\-_]/', '', (string) $value );

		if ( strlen( $reference ) < 3 ) {
			return '';
		}

		return substr( $reference, 0, 60 );
	}

	/**
	 * Auto-generate a reference from post slug or ID.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function reference( $post_id ) {
		$slug = (string) get_post_field( 'post_name', $post_id );

		if ( strlen( $slug ) >= 3 ) {
			return self::sanitize_reference( $slug );
		}

		return 'post-' . (int) $post_id;
	}

	/**
	 * Resolve the effective per-post config (meta merged over settings defaults).
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get_config( $post_id ) {
		$config = array(
			'enabled'       => (bool) get_post_meta( $post_id, self::ENABLED, true ),
			'price'         => get_post_meta( $post_id, self::PRICE, true ),
			'template'      => Gvm_Settings::default_template(),
			'hide_strategy' => (string) get_post_meta( $post_id, self::HIDE_STRATEGY, true ),
			'hide_percent'  => (int) get_post_meta( $post_id, self::HIDE_PERCENT, true ),
			'hide_sections' => (int) get_post_meta( $post_id, self::HIDE_SECTIONS, true ),
			'hide_words'    => (int) get_post_meta( $post_id, self::HIDE_WORDS, true ),
			'reference'     => (string) get_post_meta( $post_id, self::REFERENCE, true ),
			'cond'          => (string) get_post_meta( $post_id, self::COND, true ),
			'redirect'      => (bool) get_post_meta( $post_id, self::REDIRECT, true ),
			'download'      => (string) get_post_meta( $post_id, self::DOWNLOAD, true ),
		);

		$price = (float) $config['price'];
		if ( $price <= 0 ) {
			$config['price'] = Gvm_Settings::default_price();
		}

		if ( ! in_array( $config['hide_strategy'], array( 'none', 'blur', 'hide', 'mangle-blur' ), true ) ) {
			$config['hide_strategy'] = 'hide';
		}

		if ( '' === $config['reference'] ) {
			$config['reference'] = self::reference( $post_id );
		}

		return $config;
	}
}
