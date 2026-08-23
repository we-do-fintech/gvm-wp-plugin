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
	 * Verify a gvm-signature.
	 *
	 * Matches gvm-sdk / gvm backend (Go) `CalculateSignatureWithParams`:
	 *
	 *   hex( HMAC-SHA256( $commitment_id . $tenant . $reference . $status . $secret, $secret ) )
	 *
	 * Note: the secret is appended to the message in addition to being the HMAC key.
	 *
	 * @param string $secret        HMAC key (from options).
	 * @param string $commitment_id Commitment id query param.
	 * @param string $tenant        Tenant query param.
	 * @param string $reference     Reference query param.
	 * @param string $status        Status query param.
	 * @param string $signature     Signature query param.
	 * @return bool
	 */
	public static function verify( $secret, $commitment_id, $tenant, $reference, $status, $signature ) {
		if ( '' === (string) $secret || '' === (string) $commitment_id || '' === (string) $signature ) {
			return false;
		}

		$expected = hash_hmac(
			'sha256',
			$commitment_id . $tenant . $reference . $status . $secret,
			$secret
		);

		return hash_equals( $expected, (string) $signature );
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
			'signature'     => isset( $_GET['gvm-signature'] ) ? sanitize_text_field( wp_unslash( $_GET['gvm-signature'] ) ) : '',
		);
	}

	/**
	 * Verify the current request's gvm-signature query param.
	 *
	 * Returns true only when gvm-status is an unlock state ("resolved" or
	 * "duplicated") AND the HMAC-SHA256 signature matches the stored secret.
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
			$params['signature']
		);
	}
}
