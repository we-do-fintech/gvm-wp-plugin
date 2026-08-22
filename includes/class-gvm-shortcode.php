<?php
/**
 * Shortcodes: [gvm] and [gvm-protected-content].
 *
 * @package Gvm_Wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the shortcodes.
 */
class Gvm_Shortcode {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_shortcode( 'gvm', array( __CLASS__, 'shortcode_gvm' ) );
		add_shortcode( 'gvm-protected-content', array( __CLASS__, 'shortcode_protected' ) );
	}

	/**
	 * Shared shortcode attribute defaults.
	 *
	 * @return array
	 */
	private static function atts() {
		return array(
			'price'          => Gvm_Settings::default_price(),
			'template'       => Gvm_Settings::default_template(),
			'hide_strategy'  => 'hide',
			'hide_percent'   => 0,
			'hide_sections'  => 6,
			'hide_words'     => 0,
			'reference'      => '',
			'title'          => '',
		);
	}

	/**
	 * [gvm] shortcode: wrap content in a client-side paywall.
	 *
	 * @param array  $atts    Attributes.
	 * @param string $content Enclosed content.
	 * @return string
	 */
	public static function shortcode_gvm( $atts, $content = null ) {
		$atts = shortcode_atts( self::atts(), self::normalize_atts( $atts ), 'gvm' );

		return Gvm_Render::paywall(
			null === $content ? '' : do_shortcode( $content ),
			array(
				'price'          => $atts['price'],
				'template'       => $atts['template'],
				'hide_strategy'  => $atts['hide_strategy'],
				'hide_percent'   => $atts['hide_percent'],
				'hide_sections'  => $atts['hide_sections'],
				'hide_words'     => $atts['hide_words'],
				'reference'      => $atts['reference'],
				'metadata_title' => $atts['title'],
			)
		);
	}

	/**
	 * [gvm-protected-content] shortcode: reveal content only after a valid signature.
	 *
	 * @param array  $atts    Attributes.
	 * @param string $content Enclosed content.
	 * @return string
	 */
	public static function shortcode_protected( $atts, $content = null ) {
		$atts = shortcode_atts( self::atts(), self::normalize_atts( $atts ), 'gvm-protected-content' );

		if ( self::is_unlocked( (string) $atts['reference'] ) ) {
			return null === $content ? '' : do_shortcode( $content );
		}

		return Gvm_Render::paywall(
			'',
			array(
				'price'          => $atts['price'],
				'template'       => $atts['template'],
				'hide_strategy'  => $atts['hide_strategy'],
				'hide_percent'   => $atts['hide_percent'],
				'hide_sections'  => $atts['hide_sections'],
				'hide_words'     => $atts['hide_words'],
				'reference'      => $atts['reference'],
				'metadata_title' => $atts['title'],
			)
		);
	}

	/**
	 * Normalize shortcode attributes so hyphen and underscore forms are equivalent
	 * (e.g. hide-strategy and hide_strategy).
	 *
	 * @param array $atts Raw shortcode attributes.
	 * @return array
	 */
	private static function normalize_atts( $atts ) {
		$atts      = is_array( $atts ) ? $atts : array();
		$normalized = array();

		foreach ( $atts as $key => $value ) {
			$normalized[ str_replace( '-', '_', (string) $key ) ] = $value;
		}

		return $normalized;
	}

	/**
	 * Whether the current request carries a valid signature for the given reference.
	 *
	 * @param string $reference Expected reference (empty = any reference).
	 * @return bool
	 */
	private static function is_unlocked( $reference ) {
		if ( ! Gvm_Signature::verify_query_signature() ) {
			return false;
		}

		if ( '' === $reference ) {
			return true;
		}

		$params = Gvm_Signature::from_request();

		return hash_equals( $reference, $params['reference'] );
	}
}
