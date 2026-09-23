<?php
/**
 * Script loading and body-level data-gvm attributes.
 *
 * @package Gvm_Wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers gvm.js as a module and injects body data-gvm-* attributes.
 */
class Gvm_Enqueue {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );
		add_action( 'wp_footer', array( __CLASS__, 'maybe_print_footer' ), 9 );
		add_filter( 'script_loader_tag', array( __CLASS__, 'filter_script_tag' ), 10, 2 );
	}

	/**
	 * Register (not enqueue) gvm.js as a module. Enqueued only when a paywall renders.
	 *
	 * Uses wp_register_script_module (WP 6.3+) when available, otherwise falls back
	 * to wp_register_script with a type="module" script_loader_tag filter.
	 *
	 * @return void
	 */
	public static function register_scripts() {
		$url = Gvm_Settings::sdk_url();

		if ( function_exists( 'wp_register_script_module' ) ) {
			wp_register_script_module(
				'gvm',
				$url,
				array(),
				GVM_WP_VERSION
			);

			return;
		}

		wp_register_script( 'gvm', $url, array(), GVM_WP_VERSION, true );
	}

	/**
	 * Enqueue gvm.js for the current request (module aware).
	 *
	 * @return void
	 */
	public static function enqueue() {
		if ( function_exists( 'wp_enqueue_script_module' ) ) {
			wp_enqueue_script_module( 'gvm' );

			return;
		}

		wp_enqueue_script( 'gvm' );
	}

	/**
	 * Add type="module" to the gvm.js tag on WordPress < 6.3.
	 *
	 * @param string $tag    The script tag HTML.
	 * @param string $handle Script handle.
	 * @return string
	 */
	public static function filter_script_tag( $tag, $handle ) {
		if ( 'gvm' !== $handle || function_exists( 'wp_enqueue_script_module' ) ) {
			return $tag;
		}

		if ( false === strpos( $tag, 'type="module"' ) ) {
			$tag = str_replace( 'type="text/javascript"', 'type="module"', $tag );
			$tag = str_replace( "type='text/javascript'", 'type="module"', $tag );
		}

		return $tag;
	}

	/**
	 * Print body attributes and template definitions when a paywall is present.
	 *
	 * @return void
	 */
	public static function maybe_print_footer() {
		if ( ! Gvm_Render::is_present() ) {
			return;
		}

		self::print_body_script();
		echo Gvm_Render::render_templates(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template fragments are plugin-controlled markup.
		echo Gvm_Render::render_styles(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugin-controlled inline CSS.
	}

	/**
	 * Print a tiny inline script that sets data-gvm-* attributes on <body>.
	 *
	 * WordPress has no core filter for <body> attributes, so we set them at
	 * runtime before the (deferred) module executes. Themes may instead call
	 * gvm_body_attributes() directly inside their <body> tag.
	 *
	 * @return void
	 */
	private static function print_body_script() {
		printf(
			"<script>(function(){var b=document.body,a=%s;for(var k in a)b.setAttribute(k,a[k]);})();</script>\n",
			wp_json_encode( self::body_attributes() )
		);
	}

	/**
	 * Return the body-level data-gvm attributes.
	 *
	 * @return array
	 */
	public static function body_attributes() {
		$attrs = array(
			'data-gvm'          => '',
			'data-gvm-tenant'   => Gvm_Settings::tenant(),
			'data-gvm-currency' => Gvm_Settings::currency(),
			'data-gvm-env'      => Gvm_Settings::env(),
		);

		$endpoint = Gvm_Settings::endpoint();
		if ( '' !== $endpoint ) {
			$attrs['data-gvm-endpoint'] = $endpoint;
		}

		$callback = Gvm_Settings::callback();
		if ( '' !== $callback ) {
			$attrs['data-gvm-callback'] = $callback;
		}

		$condition = Gvm_Render::condition();
		if ( '' !== $condition ) {
			$attrs['data-gvm-cond'] = $condition;
		}

		$analytics = Gvm_Settings::analytics();
		if ( ! empty( $analytics ) ) {
			$attrs['data-gvm-analytics'] = implode( ',', $analytics );
		}

		return $attrs;
	}
}

/**
 * Echo body attributes. Use inside <body <?php gvm_body_attributes(); ?>> to
 * print the attributes statically instead of relying on the runtime script.
 *
 * @return void
 */
function gvm_body_attributes() {
	$attrs = Gvm_Enqueue::body_attributes();

	foreach ( $attrs as $key => $value ) {
		if ( '' === $value ) {
			echo ' ' . esc_attr( $key );
		} else {
			echo ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
		}
	}
}
