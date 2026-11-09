<?php
/**
 * Settings storage: defaults, merged read (option `sr_settings`), whitelist sanitize
 * for the Settings API.
 *
 * Contract (0.2.5):
 * - defaults():  the eight settings keys with verbatim default values.
 * - get():       stored partial option merged OVER defaults via wp_parse_args.
 * - sanitize():  whitelist scope, normalize scope_urls + wallpaper_selectors
 *                (textarea string → array, trim/strip/cap), cast flags to bool.
 *                Always returns every key.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SR_Settings' ) ) {
	class SR_Settings {

		const OPTION_KEY = 'sr_settings';

		public static function defaults() {
			return array(
				'scope'               => 'home',
				'scope_urls'          => array(),
				'auto_return'         => true,
				'sound_default'       => true,
				'tour_default'        => true,
				'transparent_backdrop' => true,
				'wallpaper_selectors' => array(),
				'mobile_enabled'      => false,
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
			// as a newline-separated string, not an array. Same for
			// wallpaper_selectors.
			if ( isset( $input['scope_urls'] ) && is_string( $input['scope_urls'] ) ) {
				$input['scope_urls'] = preg_split( '/\r\n|\r|\n/', $input['scope_urls'] );
			}
			if ( isset( $input['wallpaper_selectors'] ) && is_string( $input['wallpaper_selectors'] ) ) {
				$input['wallpaper_selectors'] = preg_split( '/\r\n|\r|\n/', $input['wallpaper_selectors'] );
			}

			$scope = ( isset( $input['scope'] ) && in_array( $input['scope'], array( 'home', 'urls', 'site' ), true ) )
				? $input['scope']
				: $defaults['scope'];

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

			// Wallpaper selectors: plain CSS selectors, one per line. Strip tags
			// (they end up in querySelectorAll, never in HTML output), cap the
			// count and length so a fat-fingered admin can't stall the page.
			$selectors = array();
			if ( isset( $input['wallpaper_selectors'] ) && is_array( $input['wallpaper_selectors'] ) ) {
				foreach ( $input['wallpaper_selectors'] as $raw ) {
					$sel = trim( wp_strip_all_tags( (string) $raw ) );
					if ( '' === $sel ) {
						continue;
					}
					if ( mb_strlen( $sel ) > 200 ) {
						$sel = mb_substr( $sel, 0, 200 );
					}
					$selectors[] = $sel;
					if ( count( $selectors ) >= 5 ) {
						break;
					}
				}
			}

			return array(
				'scope'                => $scope,
				'scope_urls'           => $urls,
				'auto_return'          => (bool) ( $input['auto_return'] ?? false ),
				'sound_default'        => (bool) ( $input['sound_default'] ?? false ),
				'tour_default'         => (bool) ( $input['tour_default'] ?? false ),
				'transparent_backdrop' => (bool) ( $input['transparent_backdrop'] ?? false ),
				'wallpaper_selectors'  => $selectors,
				'mobile_enabled'       => (bool) ( $input['mobile_enabled'] ?? false ),
			);
		}
	}
}
