<?php
/**
 * Price token tests. Run: php tests/price-token-vector.php
 *
 * Shared vector with gvm-lite and gvm-sdk (token format v1).
 */

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['gvm_test_options'] = array();

function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['gvm_test_options'] ) ? $GLOBALS['gvm_test_options'][ $key ] : $default;
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, (array) $args );
}

function wp_unslash( $v ) {
	return $v;
}

function apply_filters( $tag, $value ) {
	return $value;
}

require_once __DIR__ . '/../includes/class-gvm-settings.php';
require_once __DIR__ . '/../includes/class-gvm-signature.php';
require_once __DIR__ . '/../includes/class-gvm-render.php';

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

$secret = 'KYPKS1pVnIneMfsb01bPPzLD1ScWMGRKVTguOAspmB9J5KZNwoO1jRNurMMtoHbh';
$vector = 'v1.eed55e8cd934210837f54f96e717b85c5eae5c2cc5d170819062fd73fc8a2312';

check(
	'shared test vector',
	$vector,
	Gvm_Signature::price_token( $secret, 'example-tenant', 'example_reference', 115, 'PLN' )
);

check(
	'currency is uppercased',
	$vector,
	Gvm_Signature::price_token( $secret, 'example-tenant', 'example_reference', 115, 'pln' )
);

check(
	'empty secret yields empty token',
	'',
	Gvm_Signature::price_token( '', 'example-tenant', 'example_reference', 115, 'PLN' )
);

$GLOBALS['gvm_test_options'] = array(
	'gvm_tenant'   => 'example-tenant',
	'gvm_secret'   => $secret,
	'gvm_currency' => 'PLN',
);

check( 'render token for price "1.15"', $vector, Gvm_Render::price_token_for( 'example_reference', '1.15' ) );
check(
	'amount always matches data-gvm-price (comma input handled as format_price does)',
	(int) round( (float) Gvm_Render::format_price( '1,15' ) * 100 ),
	Gvm_Render::amount_minor( '1,15' )
);
check( 'render token for float 1.15', $vector, Gvm_Render::price_token_for( 'example_reference', 1.15 ) );
check(
	'render token differs for another price',
	false,
	Gvm_Render::price_token_for( 'example_reference', '2.99' ) === $vector
);
check( 'amount minor: 19.99 -> 1999', 1999, Gvm_Render::amount_minor( 19.99 ) );

// Decimal comma (Polish locale).
check( 'parse "1,15"', 1.15, Gvm_Settings::parse_price( '1,15' ) );
check( 'parse "1.15"', 1.15, Gvm_Settings::parse_price( '1.15' ) );
check( 'parse " 1,15 zł"', 1.15, Gvm_Settings::parse_price( " 1,15\u{00A0}zł " ) );
check( 'parse "1,5"', 1.5, Gvm_Settings::parse_price( '1,5' ) );
check( 'parse "1,159" rounds', 1.16, Gvm_Settings::parse_price( '1,159' ) );
check( 'parse float 1.15', 1.15, Gvm_Settings::parse_price( 1.15 ) );
check( 'parse "50,00" max ok', 50.0, Gvm_Settings::parse_price( '50,00' ) );
foreach ( array( 'abc', '', '-1,15', '50,01', '0', '0,00', '1,2,3', '1.234,50', '1 234,50', '1e3', null ) as $bad ) {
	check( 'reject ' . var_export( $bad, true ), null, Gvm_Settings::parse_price( $bad ) );
}
check( 'sanitize_price "1,15" -> "1.15"', '1.15', Gvm_Settings::sanitize_price( '1,15' ) );
check( 'sanitize_price clamps 99 -> "50.00"', '50.00', Gvm_Settings::sanitize_price( '99' ) );
check( 'sanitize_price garbage -> default', '0.99', Gvm_Settings::sanitize_price( 'abc' ) );
check( 'format_price "1,15"', '1.15', Gvm_Render::format_price( '1,15' ) );
check( 'format_price "99,5" clamps', '50.00', Gvm_Render::format_price( '99,5' ) );
check( 'format_price garbage -> default', '0.99', Gvm_Render::format_price( 'abc' ) );
check( 'amount_minor "1,15" -> 115', 115, Gvm_Render::amount_minor( '1,15' ) );
check( 'token for "1,15" equals vector', $vector, Gvm_Render::price_token_for( 'example_reference', '1,15' ) );

$GLOBALS['gvm_test_options']['gvm_secret'] = '';
check( 'no secret -> no token attribute value', '', Gvm_Render::price_token_for( 'example_reference', '1.15' ) );

if ( $failures > 0 ) {
	echo "\n{$failures} failure(s)\n";
	exit( 1 );
}
echo "\nall passed\n";
