<?php
/**
 * Redirect signature tests. Run: php tests/signature-vector.php
 *
 * Payload (contract §4/§5): commitmentId . tenant . reference . status . signed_at . secret,
 * HMAC-SHA256 keyed with the tenant secret.
 */

define( 'ABSPATH', __DIR__ . '/' );

function sanitize_text_field( $v ) {
	return trim( (string) $v );
}

function wp_unslash( $v ) {
	return $v;
}

require_once __DIR__ . '/../includes/class-gvm-signature.php';

$failures = 0;

function check( $name, $expected, $actual ) {
	global $failures;
	if ( $expected === $actual ) {
		echo "ok   - {$name}\n";
		return;
	}
	$failures++;
	echo "FAIL - {$name}\n  expected: " . var_export( $expected, true ) . "\n  actual:   " . var_export( $actual, true ) . "\n";
}

$secret    = 'KYPKS1pVnIneMfsb01bPPzLD1ScWMGRKVTguOAspmB9J5KZNwoO1jRNurMMtoHbh';
$cid       = 'c0ffee00-0000-4000-8000-000000000001';
$tenant    = 'example-tenant';
$reference = 'example_reference';
$status    = 'resolved';
$signed_at = '1793000000';
$now       = 1793000000;

$signature = hash_hmac( 'sha256', $cid . $tenant . $reference . $status . $signed_at . $secret, $secret );
$legacy    = hash_hmac( 'sha256', $cid . $tenant . $reference . $status . $secret, $secret );

check( 'valid fresh signature', true, Gvm_Signature::verify( $secret, $cid, $tenant, $reference, $status, $signed_at, $signature, $now ) );
check( 'valid at max age (300 s)', true, Gvm_Signature::verify( $secret, $cid, $tenant, $reference, $status, $signed_at, $signature, $now + 300 ) );
check( 'rejected when older than 300 s', false, Gvm_Signature::verify( $secret, $cid, $tenant, $reference, $status, $signed_at, $signature, $now + 301 ) );
check( 'valid with 30 s clock skew', true, Gvm_Signature::verify( $secret, $cid, $tenant, $reference, $status, $signed_at, $signature, $now - 30 ) );
check( 'rejected more than 30 s in the future', false, Gvm_Signature::verify( $secret, $cid, $tenant, $reference, $status, $signed_at, $signature, $now - 31 ) );
check( 'rejected when signed_at is missing', false, Gvm_Signature::verify( $secret, $cid, $tenant, $reference, $status, '', $signature, $now ) );
check( 'rejected when signed_at is not an integer', false, Gvm_Signature::verify( $secret, $cid, $tenant, $reference, $status, '1793000000.5', $signature, $now ) );
check( 'rejected legacy signature without signed_at', false, Gvm_Signature::verify( $secret, $cid, $tenant, $reference, $status, $signed_at, $legacy, $now ) );
check( 'rejected when signed_at is tampered', false, Gvm_Signature::verify( $secret, $cid, $tenant, $reference, $status, '1793000001', $signature, $now ) );
check( 'rejected when status is tampered', false, Gvm_Signature::verify( $secret, $cid, $tenant, $reference, 'duplicated', $signed_at, $signature, $now ) );
check( 'rejected with another secret', false, Gvm_Signature::verify( 'other-secret', $cid, $tenant, $reference, $status, $signed_at, $signature, $now ) );
check( 'rejected with empty secret', false, Gvm_Signature::verify( '', $cid, $tenant, $reference, $status, $signed_at, $signature, $now ) );

$_GET = array(
	'gvm-tenant'        => $tenant,
	'gvm-reference'     => $reference,
	'gvm-commitment-id' => $cid,
	'gvm-status'        => $status,
	'gvm-signed-at'     => $signed_at,
	'gvm-signature'     => $signature,
);
$params = Gvm_Signature::from_request();
check( 'from_request reads gvm-signed-at', $signed_at, $params['signed_at'] );
check(
	'from_request params verify',
	true,
	Gvm_Signature::verify( $secret, $params['commitment_id'], $params['tenant'], $params['reference'], $params['status'], $params['signed_at'], $params['signature'], $now )
);

unset( $_GET['gvm-signed-at'] );
check( 'from_request defaults signed_at to empty', '', Gvm_Signature::from_request()['signed_at'] );

if ( $failures > 0 ) {
	echo "\n{$failures} failure(s)\n";
	exit( 1 );
}
echo "\nall passed\n";
