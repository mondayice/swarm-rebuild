<?php
/**
 * Settings storage: defaults, merged read (option `sr_settings`), whitelist sanitize
 * for the Settings API.
 *
 * Contract (pinned in the plan):
 * - defaults():  the eight settings keys with verbatim default values.
 * - get():       stored partial option merged OVER defaults via wp_parse_args.
 * - sanitize():  whitelist scope, normalize scope_urls (trim, strip home_url prefix,
 *                strip slashes, drop empties), cast flags to bool, delay = floatval
 *                within [0.5, 10] else fallback 2.5. Always returns every key.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SR_Settings' ) ) {
	class SR_Settings {

		const OPTION_KEY = 'sr_settings';

		public static function defaults() {
			return array(
				'scope'          => 'home',
				'scope_urls'     => array(),
				'intro_enabled'  => false,
				'card_enabled'   => true,
				'sound_default'  => true,
				'hotkey_enabled' => true,
				'mobile_enabled' => false,
				'canvas_bg'      => 'page',
				'intro_delay'    => 2.5,
			);
		}

		public static function get() {
			return wp_parse_args( get_option( self::OPTION_KEY, array() ), self::defaults() );
		}

		/**
		 * Sanitize callback for register_setting(). Accepts array or null
		 * (unchecked-checkbox submissions may arrive as null/partial).
		 *
		 * @param mixed $input Raw submitted settings.
		 * @return array Full settings array, key set identical to defaults().
		 */
		public static function sanitize( $input ) {
			$defaults = self::defaults();
			if ( ! is_array( $input ) ) {
				return $defaults;
			}

			// The scope_urls field is a textarea: the Settings API delivers it
			// as a newline-separated string, not an array.
			if ( isset( $input['scope_urls'] ) && is_string( $input['scope_urls'] ) ) {
				$input['scope_urls'] = preg_split( '/\r\n|\r|\n/', $input['scope_urls'] );
			}

			$scope = ( isset( $input['scope'] ) && in_array( $input['scope'], array( 'home', 'urls', 'site' ), true ) )
				? $input['scope']
				: $defaults['scope'];

			$canvas_bg = ( isset( $input['canvas_bg'] ) && in_array( $input['canvas_bg'], array( 'page', 'transparent', 'white' ), true ) )
				? $input['canvas_bg']
				: $defaults['canvas_bg'];

			$urls = array();
			if ( isset( $input['scope_urls'] ) && is_array( $input['scope_urls'] ) ) {
				$base = home_url();
				foreach ( $input['scope_urls'] as $raw ) {
					$line = trim( (string) $raw );
					if ( '' === $line ) {
						continue;
					}
					if ( 0 === strpos( $line, $base ) ) {
						$line = substr( $line, strlen( $base ) );
					}
					$line = trim( $line, '/' );
					if ( '' === $line ) {
						continue;
					}
					$urls[] = $line;
				}
			}

			$delay = isset( $input['intro_delay'] ) ? floatval( $input['intro_delay'] ) : 0.0;
			if ( $delay < 0.5 || $delay > 10 ) {
				$delay = $defaults['intro_delay'];
			}

			return array(
				'scope'          => $scope,
				'scope_urls'     => $urls,
				'intro_enabled'  => (bool) ( $input['intro_enabled'] ?? false ),
				'card_enabled'   => (bool) ( $input['card_enabled'] ?? false ),
				'sound_default'  => (bool) ( $input['sound_default'] ?? false ),
				'hotkey_enabled' => (bool) ( $input['hotkey_enabled'] ?? false ),
				'mobile_enabled' => (bool) ( $input['mobile_enabled'] ?? false ),
				'canvas_bg'      => $canvas_bg,
				'intro_delay'    => $delay,
			);
		}
	}
}
