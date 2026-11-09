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

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string, $remove_breaks = false ) {
		$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
		$string = strip_tags( $string );
		if ( $remove_breaks ) {
			$string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
		}
		return trim( $string );
	}
}

function home_url( $path = '' ) {
	return 'https://example.com' . $path;
}

function untrailingslashit( $value ) {
	return rtrim( $value, '/' );
}

function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['__sr_actions'][] = array( $tag, $callback, $priority );
	return true;
}

function add_shortcode( $tag, $callback ) {
	$GLOBALS['__sr_registered_tag'] = $tag;
	return true;
}

function shortcode_atts( $defaults, $atts, $shortcode = '' ) {
	$out = array();
	foreach ( $defaults as $key => $value ) {
		$out[ $key ] = array_key_exists( $key, (array) $atts ) ? ( (array) $atts )[ $key ] : $value;
	}
	return $out;
}

function wp_json_encode( $data ) {
	return json_encode( $data );
}

function wp_get_inline_script_tag( $code, $attrs = array() ) {
	$pairs = '';
	foreach ( $attrs as $key => $value ) {
		$pairs .= ' ' . $key . '="' . $value . '"';
	}
	return '<script' . $pairs . '>' . $code . '</script>';
}

function __( $text, $domain = 'default' ) {
	return $text;
}

function add_query_arg( $key, $value, $url ) {
	return $url . ( strpos( $url, '?' ) === false ? '?' : '&' ) . $key . '=' . rawurlencode( $value );
}

function has_shortcode( $content, $tag ) {
	return false !== strpos( (string) $content, '[' . $tag );
}

function get_post_meta( $post_id, $key, $single = false ) {
	$meta = isset( $GLOBALS['__sr_post_meta'][ $post_id ][ $key ] ) ? $GLOBALS['__sr_post_meta'][ $post_id ][ $key ] : array();
	return $single ? ( isset( $meta[0] ) ? $meta[0] : '' ) : $meta;
}

function get_post() {
	return null;
}

function is_front_page() {
	return false;
}

function is_home() {
	return false;
}

function wp_enqueue_script_module( $handle, $src, $deps = array(), $version = false ) {
	$GLOBALS['__sr_enqueued'][] = array( 'module', $handle, $src );
	return true;
}

function wp_enqueue_style( $handle, $src, $deps = array(), $version = false ) {
	$GLOBALS['__sr_enqueued'][] = array( 'style', $handle, $src );
	return true;
}

if ( ! defined( 'SWARM_REBUILD_URL' ) ) {
	define( 'SWARM_REBUILD_URL', 'https://example.com/wp-content/plugins/swarm-rebuild/' );
}
if ( ! defined( 'SWARM_REBUILD_VERSION' ) ) {
	define( 'SWARM_REBUILD_VERSION', '0.1.0' );
}

require_once dirname( __DIR__, 2 ) . '/includes/class-sr-settings.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-sr-frontend.php';
