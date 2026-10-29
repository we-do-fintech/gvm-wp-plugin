<?php
/**
 * Consent gate tests. Run: php tests/template-guard.php
 *
 * Mirrors gvm-sdk-admin schema/validate.test.ts (validateTemplateConsent).
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'GVM_WP_DIR', dirname( __DIR__ ) . '/' );

$GLOBALS['gvm_test_options'] = array();
$GLOBALS['gvm_test_filters'] = array();

function __( $text ) {
	return $text;
}

function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['gvm_test_options'] ) ? $GLOBALS['gvm_test_options'][ $key ] : $default;
}

function update_option( $key, $value ) {
	$GLOBALS['gvm_test_options'][ $key ] = $value;
	return true;
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, (array) $args );
}

function wp_unslash( $v ) {
	return $v;
}

function apply_filters( $tag, $value ) {
	return isset( $GLOBALS['gvm_test_filters'][ $tag ] ) ? $GLOBALS['gvm_test_filters'][ $tag ] : $value;
}

$gvm_test_log = tempnam( sys_get_temp_dir(), 'gvmlog' );
ini_set( 'error_log', $gvm_test_log );

require_once __DIR__ . '/../includes/class-gvm-settings.php';
require_once __DIR__ . '/../includes/class-gvm-signature.php';
require_once __DIR__ . '/../includes/class-gvm-template-guard.php';
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

$consent = '<p>Szczegóły w <a href="https://getviamsg.com/terms" target="_blank">Regulaminie</a>.</p>'
	. '<label><input type="checkbox" data-gvm-accept-terms="[data-gvm-bind-send-sms]" data-gvm-accept-terms-version="pl-2026-10-23" /><span>Żądam…</span></label>';

check( 'no trigger -> valid', array(), Gvm_Template_Guard::errors( '<p>info</p>' ) );
check( 'payment with consent -> valid', array(), Gvm_Template_Guard::errors( '<div data-gvm-bind-qr></div>' . $consent . '<button data-gvm-bind-send-sms>SMS</button>' ) );

foreach ( array( 'data-gvm-bind-qr', 'data-gvm-bind-send-sms', 'data-gvm-bind-unlock' ) as $trigger ) {
	check( "{$trigger} without gate -> 2 errors", 2, count( Gvm_Template_Guard::errors( "<button {$trigger}>x</button>" ) ) );
}
foreach ( array( 'data-gvm-bind-pay', 'data-gvm-price="1.99"' ) as $trigger ) {
	check( "{$trigger} alone (paywall) -> valid", array(), Gvm_Template_Guard::errors( "<button {$trigger}>x</button>" ) );
}

$checked = Gvm_Template_Guard::errors( '<button data-gvm-bind-send-sms>x</button>' . str_replace( '/>', 'checked />', $consent ) );
check( 'pre-checked checkbox -> 1 error', 1, count( $checked ) );
check( 'pre-checked message', true, false !== strpos( $checked[0], 'unchecked by default' ) );

check( 'checkbox without data-gvm-accept-terms -> 1 error', 1, count( Gvm_Template_Guard::errors( '<button data-gvm-bind-send-sms>x</button><input type="checkbox" /><a href="https://getviamsg.com/terms">R</a>' ) ) );
check( 'terms link to another domain -> 1 error', 1, count( Gvm_Template_Guard::errors( '<button data-gvm-bind-send-sms>x</button>' . str_replace( 'https://getviamsg.com/terms', '/regulamin/', $consent ) ) ) );
check( 'markup in comments does not count', 2, count( Gvm_Template_Guard::errors( '<!-- ' . $consent . ' --><div data-gvm-bind-qr></div>' ) ) );
check( 'selectors in <style> are not triggers', array(), Gvm_Template_Guard::errors( '<style>[data-gvm-bind-qr] { color: red }</style>' ) );
check( 'selector with > in attribute', array(), Gvm_Template_Guard::errors( '<button data-gvm-bind-send-sms>x</button><a href="https://getviamsg.com/terms#p4">R</a><input data-gvm-accept-terms=".card > [data-gvm-bind-send-sms]" type="checkbox">' ) );

foreach ( array( 'payment', 'paywall', 'inline', 'download' ) as $slug ) {
	$html = Gvm_Render::template_html( $slug );
	check( "bundled {$slug} template passes", array(), Gvm_Template_Guard::errors( $html ) );
	check( "bundled {$slug} template links the terms", true, false !== strpos( $html, 'https://getviamsg.com/terms' ) );
	check( "bundled {$slug} template has the checkbox only in the payment dialog", 'payment' === $slug, false !== strpos( $html, 'data-gvm-accept-terms' ) );
}

$bundled = Gvm_Render::template_html( 'payment' );

$GLOBALS['gvm_test_options']['gvm_template_payment'] = '<button data-gvm-bind-send-sms>old</button>';
check( 'invalid settings template falls back to bundled', $bundled, Gvm_Render::template_html( 'payment' ) );
check( 'invalid settings template is reported', true, isset( $GLOBALS['gvm_test_options'][ Gvm_Template_Guard::OPTION_ERRORS ]['payment:settings'] ) );

$custom = '<div data-gvm-bind-qr></div>' . $consent . '<button data-gvm-bind-send-sms>SMS</button>';
$GLOBALS['gvm_test_options']['gvm_template_payment'] = $custom;
check( 'valid settings template is used', $custom, Gvm_Render::template_html( 'payment' ) );
check( 'report cleared once the settings template is valid', false, isset( $GLOBALS['gvm_test_options'][ Gvm_Template_Guard::OPTION_ERRORS ]['payment:settings'] ) );
$GLOBALS['gvm_test_options']['gvm_template_payment'] = '';

$tmp = tempnam( sys_get_temp_dir(), 'gvm' );
file_put_contents( $tmp, '<button data-gvm-bind-send-sms>theme</button>' );
$GLOBALS['gvm_test_filters']['gvm_template_path_payment'] = $tmp;
check( 'invalid filter template falls back to bundled', $bundled, Gvm_Render::template_html( 'payment' ) );
check( 'invalid filter template is reported', true, isset( $GLOBALS['gvm_test_options'][ Gvm_Template_Guard::OPTION_ERRORS ]['payment:filter'] ) );
check( 'invalid filter template is logged', true, false !== strpos( (string) file_get_contents( $gvm_test_log ), 'payment template from filter rejected' ) );

file_put_contents( $tmp, $custom );
check( 'valid filter template is used', $custom, Gvm_Render::template_html( 'payment' ) );
check( 'filter report cleared', false, isset( $GLOBALS['gvm_test_options'][ Gvm_Template_Guard::OPTION_ERRORS ]['payment:filter'] ) );
unlink( $tmp );
unlink( $gvm_test_log );

if ( $failures > 0 ) {
	echo "\n{$failures} failure(s)\n";
	exit( 1 );
}
echo "\nall passed\n";
