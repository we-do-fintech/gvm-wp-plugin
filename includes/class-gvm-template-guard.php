<?php
/**
 * Consent gate checks for paywall / payment templates.
 *
 * @package Gvm_Wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates that a template able to start a commitment (the payment dialog:
 * QR / SMS / unlock; paywalls with only a pay button are exempt) carries the consent
 * gate required by the GetViaMsg terms (regulamin 23.10.2026, § 4 ust. 2 and
 * § 11 ust. 2): an unchecked `<input type="checkbox" data-gvm-accept-terms>`
 * and a link to https://getviamsg.com/terms.
 *
 * Mirrors gvm-sdk-admin `schema/validate.ts` (validateTemplateConsent).
 */
class Gvm_Template_Guard {

	const TERMS_URL = 'https://getviamsg.com/terms';

	/**
	 * Option holding template sources that failed the check ( slug => message ).
	 */
	const OPTION_ERRORS = 'gvm_template_consent_errors';

	/**
	 * Attributes that make a template a commitment trigger.
	 *
	 * @var string[]
	 */
	const TRIGGERS = array(
		'data-gvm-bind-qr',
		'data-gvm-bind-send-sms',
		'data-gvm-bind-unlock',
	);

	/**
	 * Register the admin notice.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
	}

	/**
	 * Parse the start tags of an HTML fragment (comments, <style> and <script>
	 * bodies are ignored).
	 *
	 * @param string $html HTML.
	 * @return array[] List of array( 'name' => string, 'attributes' => array ).
	 */
	public static function parse_tags( $html ) {
		$html = preg_replace( '/<!--[\s\S]*?(?:-->|$)/', '', (string) $html );
		$html = preg_replace( '/<(style|script)\b[^>]*>[\s\S]*?<\/\1\s*>/i', '', (string) $html );

		$tags = array();
		if ( ! preg_match_all( '/<([a-zA-Z][a-zA-Z0-9-]*)((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/', (string) $html, $matches, PREG_SET_ORDER ) ) {
			return $tags;
		}

		foreach ( $matches as $match ) {
			$attributes = array();
			preg_match_all( '/([^\s"\'>\/=]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+)))?/', $match[2], $attrs, PREG_SET_ORDER );
			foreach ( $attrs as $attr ) {
				$name = strtolower( $attr[1] );
				if ( array_key_exists( $name, $attributes ) ) {
					continue;
				}
				$value = '';
				foreach ( array( 2, 3, 4 ) as $index ) {
					if ( isset( $attr[ $index ] ) && '' !== $attr[ $index ] ) {
						$value = $attr[ $index ];
						break;
					}
				}
				$attributes[ $name ] = $value;
			}
			$tags[] = array(
				'name'       => strtolower( $match[1] ),
				'attributes' => $attributes,
			);
		}

		return $tags;
	}

	/**
	 * List consent problems of a template; empty array when it is compliant
	 * (or contains no commitment trigger at all).
	 *
	 * @param string $html Template HTML.
	 * @return string[]
	 */
	public static function errors( $html ) {
		$tags = self::parse_tags( $html );

		$triggers = array();
		foreach ( $tags as $tag ) {
			foreach ( self::TRIGGERS as $trigger ) {
				if ( array_key_exists( $trigger, $tag['attributes'] ) ) {
					$triggers[ $trigger ] = true;
				}
			}
		}

		if ( empty( $triggers ) ) {
			return array();
		}

		$found  = implode( ', ', array_keys( $triggers ) );
		$errors = array();

		$checkboxes = array_filter(
			$tags,
			static function ( $tag ) {
				return 'input' === $tag['name']
					&& array_key_exists( 'data-gvm-accept-terms', $tag['attributes'] )
					&& isset( $tag['attributes']['type'] )
					&& 'checkbox' === strtolower( trim( $tag['attributes']['type'] ) );
			}
		);

		if ( empty( $checkboxes ) ) {
			$errors[] = sprintf(
				/* translators: %s: list of data-gvm-* attributes. */
				__( 'The template contains a commitment trigger (%s) but no consent checkbox: add an unchecked <input type="checkbox" data-gvm-accept-terms> (regulamin § 11 ust. 2).', 'gvm-wp' ),
				$found
			);
		} else {
			foreach ( $checkboxes as $checkbox ) {
				if ( array_key_exists( 'checked', $checkbox['attributes'] ) ) {
					$errors[] = __( 'The data-gvm-accept-terms consent checkbox must be unchecked by default: remove the "checked" attribute (regulamin § 11 ust. 2).', 'gvm-wp' );
					break;
				}
			}
		}

		$has_link = false;
		foreach ( $tags as $tag ) {
			if ( 'a' === $tag['name'] && isset( $tag['attributes']['href'] )
				&& preg_match( '#^https://(www\.)?getviamsg\.com/terms(?:[/?\#].*)?$#i', trim( $tag['attributes']['href'] ) ) ) {
				$has_link = true;
				break;
			}
		}

		if ( ! $has_link ) {
			$errors[] = sprintf(
				/* translators: 1: list of data-gvm-* attributes, 2: terms URL. */
				__( 'The template contains a commitment trigger (%1$s) but no link to the terms: add <a href="%2$s"> (regulamin § 4 ust. 2).', 'gvm-wp' ),
				$found,
				self::TERMS_URL
			);
		}

		return $errors;
	}

	/**
	 * Whether a template is compliant.
	 *
	 * @param string $html Template HTML.
	 * @return bool
	 */
	public static function is_valid( $html ) {
		return array() === self::errors( $html );
	}

	/**
	 * Record (or clear) a rejected template source for the admin notice and log it.
	 *
	 * @param string   $slug   Template slug.
	 * @param string   $source Where the template came from (settings|filter).
	 * @param string[] $errors Problems; empty clears the record.
	 * @return void
	 */
	public static function report( $slug, $source, $errors ) {
		$stored = get_option( self::OPTION_ERRORS, array() );
		$stored = is_array( $stored ) ? $stored : array();
		$key    = $slug . ':' . $source;

		if ( empty( $errors ) ) {
			if ( isset( $stored[ $key ] ) ) {
				unset( $stored[ $key ] );
				update_option( self::OPTION_ERRORS, $stored, false );
			}
			return;
		}

		$message = implode( ' ', $errors );
		if ( isset( $stored[ $key ] ) && $stored[ $key ] === $message ) {
			return;
		}

		$stored[ $key ] = $message;
		update_option( self::OPTION_ERRORS, $stored, false );

		if ( function_exists( 'error_log' ) ) {
			error_log( sprintf( '[gvm-wp] %s template from %s rejected, using the bundled template: %s', $slug, $source, $message ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Admin notice listing rejected templates.
	 *
	 * @return void
	 */
	public static function admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$stored = get_option( self::OPTION_ERRORS, array() );
		if ( ! is_array( $stored ) || empty( $stored ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'GetViaMsg: a custom template was ignored because it lacks the required consent gate. The bundled template is used instead.', 'gvm-wp' ) . '</strong></p><ul>';
		foreach ( $stored as $key => $message ) {
			list( $slug, $source ) = array_pad( explode( ':', (string) $key, 2 ), 2, '' );
			$label                 = 'filter' === $source
				/* translators: %s: template slug. */
				? sprintf( __( '"%s" template from the gvm_template_path filter', 'gvm-wp' ), $slug )
				/* translators: %s: template slug. */
				: sprintf( __( '"%s" template from Settings → GetViaMsg', 'gvm-wp' ), $slug );
			echo '<li>' . esc_html( $label . ': ' . $message ) . '</li>';
		}
		echo '</ul></div>';
	}
}
