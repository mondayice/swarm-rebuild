<?php
/**
 * Frontend controller: scope/mobile/shortcode gating, script-module enqueue,
 * JSON config tag.
 *
 * Runtime flow (hook wiring, verified on the test site — unit tests cover the
 * pure functions only):
 *   init                 -> register()               adds the shortcode
 *   wp_enqueue_scripts   -> enqueue_assets()         gates on should_enqueue(),
 *                                                    enqueues loader module + card css,
 *                                                    stashes config for wp_head
 *   wp_head (prio 1)     -> print_config()           prints #swarm-rebuild-config JSON
 *
 * Shortcode autoplay cannot be known at wp_enqueue_scripts time (shortcodes run
 * during content rendering), so the shortcode renders its own JSON marker tag
 * (#swarm-rebuild-shortcode) and the loader merges it client-side.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SR_Frontend' ) ) {
	class SR_Frontend {

		const SHORTCODE_TAG    = 'swarm_rebuild';
		const CONFIG_TAG_ID    = 'swarm-rebuild-config';
		const SHORTCODE_TAG_ID = 'swarm-rebuild-shortcode';

		/** @var array|null Stashed config for print_config(). */
		private static $config = null;

		public static function register() {
			add_shortcode( self::SHORTCODE_TAG, array( __CLASS__, 'render_shortcode' ) );
			// Registered up front at priority 2: adding it from inside
			// enqueue_assets (which itself runs on wp_head at priority 1)
			// would never execute that round — WP_Hook iterates a snapshot
			// of the running priority's callbacks and never revisits it.
			add_action( 'wp_head', array( __CLASS__, 'print_config' ), 2 );
		}

		public static function enqueue_assets() {
			$s   = SR_Settings::get();
			$ctx = self::current_ctx();
			if ( ! self::should_enqueue( $s, $ctx ) ) {
				return;
			}
			wp_enqueue_script_module( 'swarm-rebuild-loader', SWARM_REBUILD_URL . 'assets/js/loader.js', array(), SWARM_REBUILD_VERSION );
			wp_enqueue_style( 'swarm-rebuild-card', SWARM_REBUILD_URL . 'assets/css/card.css', array(), SWARM_REBUILD_VERSION );
			self::$config = self::build_config( $s, $ctx );
		}

		public static function print_config() {
			if ( null === self::$config ) {
				return;
			}
			echo wp_get_inline_script_tag(
				(string) wp_json_encode( self::$config ),
				array( 'id' => self::CONFIG_TAG_ID, 'type' => 'application/json' )
			);
		}

		/**
		 * Runtime context for gating. Shortcode detection covers the main
		 * post's content and page-builder meta (Elementor stores widgets in
		 * post meta, not content); sidebars/widgets outside the main query are
		 * not scanned — known limitation.
		 *
		 * @return array{is_home: bool, path: string, ua: string, shortcode_active: bool}
		 */
		private static function current_ctx() {
			$post = get_post();
			return array(
				'is_home'          => is_front_page() || is_home(),
				'path'             => (string) parse_url( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH ),
				'ua'               => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
				'shortcode_active' => $post ? self::detect_shortcode( $post ) : false,
			);
		}

		/**
		 * Shortcode presence for a post: its content, or page-builder meta
		 * (Elementor keeps shortcode widgets in _elementor_data while
		 * post_content stays empty).
		 *
		 * @param object $post Post-ish object with ID and post_content.
		 * @return bool
		 */
		public static function detect_shortcode( $post ) {
			if ( ! is_object( $post ) || ! isset( $post->post_content ) ) {
				return false;
			}
			if ( has_shortcode( $post->post_content, self::SHORTCODE_TAG ) ) {
				return true;
			}
			$builder_meta = get_post_meta( $post->ID, '_elementor_data', true );
			return is_string( $builder_meta ) && false !== strpos( $builder_meta, '[' . self::SHORTCODE_TAG );
		}

		/**
		 * Scope test, slash-normalized on both sides.
		 *
		 * @param array  $s       Settings.
		 * @param string $path    Request path, e.g. '/about/'.
		 * @param bool   $is_home Whether the request is the front page.
		 */
		public static function is_in_scope( array $s, $path, $is_home ) {
			$scope = $s['scope'];
			if ( 'site' === $scope ) {
				return true;
			}
			if ( 'home' === $scope ) {
				return (bool) $is_home;
			}
			$needle = trim( (string) $path, '/' );
			foreach ( (array) $s['scope_urls'] as $url ) {
				if ( trim( (string) $url, '/' ) === $needle ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Mobile detection by user agent. Desktop UA with touch (Surface etc.)
		 * stays enabled — the cursor metaphor still applies there.
		 */
		public static function is_mobile_request( $ua ) {
			return (bool) preg_match( '/Mobile|Android|iPhone|iPad|iPod|Opera Mini|IEMobile|WebOS|BlackBerry/i', (string) $ua );
		}

		/**
		 * Enqueue gate. Short-circuit order: mobile gate → shortcode → scope.
		 *
		 * @param array $s   Settings.
		 * @param array $ctx {is_home, path, ua, shortcode_active}
		 */
		public static function should_enqueue( array $s, array $ctx ) {
			if ( empty( $s['mobile_enabled'] ) && self::is_mobile_request( $ctx['ua'] ) ) {
				return false;
			}
			if ( ! empty( $ctx['shortcode_active'] ) ) {
				return true;
			}
			return self::is_in_scope( $s, $ctx['path'], $ctx['is_home'] );
		}

		/**
		 * Truthy shortcode attribute: 'true'|'1'|'yes' (case-insensitive) or bool true.
		 */
		public static function is_truthy_attr( $value ) {
			if ( is_bool( $value ) ) {
				return $value;
			}
			return in_array( strtolower( trim( (string) $value ) ), array( 'true', '1', 'yes' ), true );
		}

		/**
		 * [swarm_rebuild autoplay="true|1|yes"] — renders an invisible JSON marker
		 * the loader merges client-side. Enqueue gating happens via has_shortcode
		 * on the post content, not through this callback.
		 */
		public static function render_shortcode( $atts = array() ) {
			$a        = shortcode_atts( array( 'autoplay' => 'false' ), $atts, self::SHORTCODE_TAG );
			$autoplay = self::is_truthy_attr( $a['autoplay'] );
			return wp_get_inline_script_tag(
				(string) wp_json_encode( array( 'autoplay' => $autoplay ) ),
				array( 'id' => self::SHORTCODE_TAG_ID, 'type' => 'application/json' )
			);
		}

		/**
		 * Loader config JSON (shape pinned in the plan; engineUrl included so the
		 * loader can lazily inject the engine from plugin assets).
		 *
		 * @param array $s   Settings.
		 * @param array $ctx {is_home, path}
		 */
		public static function build_config( array $s, array $ctx ) {
			$autoplay = ! empty( $s['intro_enabled'] ) && self::is_in_scope( $s, $ctx['path'], $ctx['is_home'] );

			return array(
				'scopeActive'  => true,
				'autoplay'     => $autoplay,
				'autoplayMode' => $autoplay ? 'intro' : null,
				'hotkeyEnabled' => (bool) $s['hotkey_enabled'],
				'cardEnabled'  => (bool) $s['card_enabled'],
				'soundDefault' => (bool) $s['sound_default'],
				'introDelay'   => (float) $s['intro_delay'],
				'engineUrl'    => add_query_arg( 'ver', SWARM_REBUILD_VERSION, SWARM_REBUILD_URL . 'assets/js/engine/swarm.js' ),
				'strings'      => array(
					'tour'        => __( 'Tour', 'swarm-rebuild' ),
					'touring'     => __( 'Touring', 'swarm-rebuild' ),
					'ttTour'      => __( 'Auto-scroll while the swarm builds', 'swarm-rebuild' ),
					'ttSpeed'     => __( 'Build speed', 'swarm-rebuild' ),
					'ttSound'     => __( 'Sound (M)', 'swarm-rebuild' ),
					'ttLive'      => __( 'Fade back to the real page', 'swarm-rebuild' ),
					'ttReplay'    => __( 'Wipe and rebuild again', 'swarm-rebuild' ),
					'ttExit'      => __( 'Exit (Esc)', 'swarm-rebuild' ),
					'goLive'      => __( 'Go live', 'swarm-rebuild' ),
					'replay'      => __( 'Replay', 'swarm-rebuild' ),
					'exit'        => __( 'Exit', 'swarm-rebuild' ),
					'tapForSound' => __( 'Tap for sound', 'swarm-rebuild' ),
					'soundOn'     => __( 'Sound on (M)', 'swarm-rebuild' ),
					'soundOff'    => __( 'Sound off (M)', 'swarm-rebuild' ),
					'agentsReady' => __( '<b>%d</b> agents ready', 'swarm-rebuild' ),
					'rebuiltIn'   => __( 'Rebuilt by <b>%d</b> agents in <b>%d</b>s', 'swarm-rebuild' ),
					'sizeBadge'   => __( '%d × %d', 'swarm-rebuild' ),
					'cardReplay'  => __( 'Replay', 'swarm-rebuild' ),
					'cardClose'   => __( 'Close animation', 'swarm-rebuild' ),
					'cardSound'   => __( 'Sound', 'swarm-rebuild' ),
					'cardSoundHint' => __( 'Applies from the next page load; press M during the animation.', 'swarm-rebuild' ),
					'cardHide'    => __( 'Hide', 'swarm-rebuild' ),
					'cardShow'    => __( 'Show', 'swarm-rebuild' ),
				),
			);
		}
	}
}
