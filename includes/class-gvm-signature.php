<?php
/**
 * Server-side signature verification.
 *
 * @package Gvm_Wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verifies the gvm-signature query parameter returned by gvm.js after payment.
 */
class Gvm_Signature {

	/**
	 * Maximum age of a signature, in seconds (now - signed_at).
	 */
	const MAX_AGE = 300;

	/**
	 * Tolerated clock skew for a signature from the future, in seconds (signed_at - now).
	 */
	const MAX_SKEW = 30;

	/**
	 * Verify a gvm-signature.
	 *
	 * Matches gvm-lite `GET /commitment/:id` (contract §4/§5):
	 *
	 *   hex( HMAC-SHA256( $commitment_id . $tenant . $reference . $status . $signed_at . $secret, $secret ) )
	 *
	 * Note: the secret is appended to the message in addition to being the HMAC key.
	 * The signature is rejected when `signed_at` is missing, not an integer, older than
	 * MAX_AGE seconds or more than MAX_SKEW seconds in the future.
	 *
	 * @param string   $secret        HMAC key (from options).
	 * @param string   $commitment_id Commitment id query param.
	 * @param string   $tenant        Tenant query param.
	 * @param string   $reference     Reference query param.
	 * @param string   $status        Status query param.
	 * @param string   $signed_at     Signed-at query param (unix seconds).
	 * @param string   $signature     Signature query param.
	 * @param int|null $now           Current unix time (defaults to time(); for tests).
	 * @return bool
	 */
	public static function verify( $secret, $commitment_id, $tenant, $reference, $status, $signed_at, $signature, $now = null ) {
		if ( '' === (string) $secret || '' === (string) $commitment_id || '' === (string) $signature ) {
			return false;
		}

		$signed_at = (string) $signed_at;
		if ( 1 !== preg_match( '/^[0-9]{1,12}$/', $signed_at ) ) {
			return false;
		}

		$now = null === $now ? time() : (int) $now;
		$age = $now - (int) $signed_at;
		if ( $age > self::MAX_AGE || -$age > self::MAX_SKEW ) {
			return false;
		}

		$expected = hash_hmac(
			'sha256',
			$commitment_id . $tenant . $reference . $status . $signed_at . $secret,
			$secret
		);

		return hash_equals( $expected, (string) $signature );
	}

	/**
	 * Signed price token (format v1), verified by gvm-lite on commitment creation.
	 *
	 *   "v1." . hex( HMAC-SHA256( "v1|tenant|reference|amountMinor|CURRENCY", $secret ) )
	 *
	 * @param string $secret       HMAC key (tenant secret).
	 * @param string $tenant       Tenant id.
	 * @param string $reference    Reference id (data-gvm-reference).
	 * @param int    $amount_minor Amount in integer minor units (1.15 => 115).
	 * @param string $currency     Currency code (uppercased here).
	 * @return string Token, or empty string when the secret is empty.
	 */
	public static function price_token( $secret, $tenant, $reference, $amount_minor, $currency ) {
		if ( '' === (string) $secret ) {
			return '';
		}

		$message = 'v1|' . $tenant . '|' . $reference . '|' . (int) $amount_minor . '|' . strtoupper( (string) $currency );

		return 'v1.' . hash_hmac( 'sha256', $message, (string) $secret );
	}

	/**
	 * Read and sanitize the relevant query params.
	 *
	 * @return array
	 */
	public static function from_request() {
		return array(
			'tenant'        => isset( $_GET['gvm-tenant'] ) ? sanitize_text_field( wp_unslash( $_GET['gvm-tenant'] ) ) : '',
			'reference'     => isset( $_GET['gvm-reference'] ) ? sanitize_text_field( wp_unslash( $_GET['gvm-reference'] ) ) : '',
			'commitment_id' => isset( $_GET['gvm-commitment-id'] ) ? sanitize_text_field( wp_unslash( $_GET['gvm-commitment-id'] ) ) : '',
			'status'        => isset( $_GET['gvm-status'] ) ? sanitize_text_field( wp_unslash( $_GET['gvm-status'] ) ) : '',
			'signed_at'     => isset( $_GET['gvm-signed-at'] ) ? sanitize_text_field( wp_unslash( $_GET['gvm-signed-at'] ) ) : '',
			'signature'     => isset( $_GET['gvm-signature'] ) ? sanitize_text_field( wp_unslash( $_GET['gvm-signature'] ) ) : '',
		);
	}

	/**
	 * Verify the current request's gvm-signature query param.
	 *
	 * Returns true only when gvm-status is an unlock state ("resolved" or
	 * "duplicated"), gvm-signed-at is fresh (see verify()) AND the HMAC-SHA256
	 * signature matches the stored secret.
	 *
	 * @return bool
	 */
	public static function verify_query_signature() {
		$params = self::from_request();

		if ( ! in_array( $params['status'], array( 'resolved', 'duplicated' ), true ) ) {
			return false;
		}

		return self::verify(
			Gvm_Settings::secret(),
			$params['commitment_id'],
			$params['tenant'],
			$params['reference'],
			$params['status'],
			$params['signed_at'],
			$params['signature']
		);
	}
}
