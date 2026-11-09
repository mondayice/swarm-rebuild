<?php
/**
 * Minimal WordPress function shims for unit-testing pure plugin classes.
 * Test infrastructure only — never shipped. Array-shaped options only.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

$GLOBALS['__sr_options'] = array();

function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['__sr_options'] ) ? $GLOBALS['__sr_options'][ $key ] : $default;
}

function update_option( $key, $value ) {
	$GLOBALS['__sr_options'][ $key ] = $value;
	return true;
}

function wp_parse_args( $args, $defaults = array() ) {
	if ( is_object( $args ) ) {
		$args = get_object_vars( $args );
	}
	if ( ! is_array( $args ) ) {
		$args = array();
	}
	return array_merge( $defaults, $args );
}

function home_url( $path = '' ) {
	return 'https://example.com' . $path;
}

function untrailingslashit( $value ) {
	return rtrim( $value, '/' );
}

require_once dirname( __DIR__, 2 ) . '/includes/class-sr-settings.php';
