<?php
/**
 * Admin settings page via Settings API.
 *
 * One page under Settings, one section, fields driven by a field
 * registry and a single renderer. All output escaped; nonce provided by
 * settings_fields(); capability manage_options.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SR_Admin' ) ) {
	class SR_Admin {

		const PAGE_SLUG   = 'swarm-rebuild';
		const OPTION_GROUP = 'sr_settings_group';

		public static function register_menu() {
			add_options_page(
				esc_html__( 'Swarm Rebuild', 'swarm-rebuild' ),
				esc_html__( 'Swarm Rebuild', 'swarm-rebuild' ),
				'manage_options',
				self::PAGE_SLUG,
				array( __CLASS__, 'render_page' )
			);
		}

		public static function register_settings() {
			register_setting(
				self::OPTION_GROUP,
				SR_Settings::OPTION_KEY,
				array(
					'sanitize_callback' => array( 'SR_Settings', 'sanitize' ),
					'show_in_rest'      => false,
				)
			);

			add_settings_section(
				'sr_main',
				esc_html__( 'Swarm Rebuild Settings', 'swarm-rebuild' ),
				array( __CLASS__, 'render_section_intro' ),
				self::PAGE_SLUG
			);

			foreach ( self::fields() as $id => $field ) {
				add_settings_field(
					'sr_' . $id,
					$field['label'],
					array( __CLASS__, 'render_field' ),
					self::PAGE_SLUG,
					'sr_main',
					array( 'field' => $field, 'key' => $id )
				);
			}

			// The config JSON travels inside the page HTML: page caches serve the
			// OLD settings until purged. Best-effort purge on every save.
			add_action( 'update_option_' . SR_Settings::OPTION_KEY, array( __CLASS__, 'flush_page_cache' ) );
			add_action( 'add_option_' . SR_Settings::OPTION_KEY, array( __CLASS__, 'flush_page_cache' ) );
		}

		/**
		 * Best-effort purge of common full-page caches after a settings save.
		 * The visitor-facing config JSON is baked into cached HTML, so without
		 * this, changed settings (tour/sound/autoreturn…) keep showing the old
		 * values to cached visitors.
		 */
		public static function flush_page_cache() {
			if ( function_exists( 'wp_cache_clear_cache' ) ) { // WP Super Cache
				wp_cache_clear_cache();
			}
			if ( function_exists( 'w3tc_flush_all' ) ) { // W3 Total Cache
				w3tc_flush_all();
			}
			if ( function_exists( 'rocket_clean_domain' ) ) { // WP Rocket
				rocket_clean_domain();
			}
			do_action( 'litespeed_purge_all' ); // LiteSpeed Cache (no-op if absent)
		}

		/**
		 * Field registry: id => label, type, extras.
		 */
		private static function fields() {
			return array(
				'scope'          => array(
					'label'   => esc_html__( 'Where to enable', 'swarm-rebuild' ),
					'type'    => 'select',
					'choices' => array(
						'home' => esc_html__( 'Front page only', 'swarm-rebuild' ),
						'urls' => esc_html__( 'Specific URLs', 'swarm-rebuild' ),
						'site' => esc_html__( 'Entire site', 'swarm-rebuild' ),
					),
				),
				'scope_urls'     => array(
					'label'       => esc_html__( 'Specific URLs', 'swarm-rebuild' ),
					'type'        => 'textarea',
					'description' => esc_html__( 'One path per line (e.g. about or pricing). Used when "Specific URLs" is selected.', 'swarm-rebuild' ),
				),
				'auto_return'    => array(
					'label'       => esc_html__( 'Return to the live page when finished', 'swarm-rebuild' ),
					'type'        => 'checkbox',
					'description' => esc_html__( 'The rebuild plays on every visit; finished links are clickable while it builds. When on, the canvas exits back to the live page within half a second of the rebuild completing. When off, the rebuilt page stays until the visitor clicks a link or exits.', 'swarm-rebuild' ),
				),
				'sound_default'  => array(
					'label'       => esc_html__( 'Sound on by default', 'swarm-rebuild' ),
					'type'        => 'checkbox',
					'description' => esc_html__( 'Visitors can mute on the card; the choice sticks in their browser.', 'swarm-rebuild' ),
				),
				'tour_default'   => array(
					'label'       => esc_html__( 'Tour on by default', 'swarm-rebuild' ),
					'type'        => 'checkbox',
					'description' => esc_html__( 'Auto-scroll follows the swarm while it builds. Visitors can pause it on the card.', 'swarm-rebuild' ),
				),
				'transparent_backdrop' => array(
					'label'       => esc_html__( 'Transparent backdrop', 'swarm-rebuild' ),
					'type'        => 'checkbox',
					'description' => esc_html__( 'Detect the site wallpaper (fixed canvas/video/image or pseudo-element background layers) and keep it visible during the animation. Turning this off always uses a solid-color wipe — the safe mode if the wallpaper misbehaves on your theme.', 'swarm-rebuild' ),
				),
				'wallpaper_selectors'  => array(
					'label'       => esc_html__( 'Wallpaper elements (CSS selectors)', 'swarm-rebuild' ),
					'type'        => 'textarea',
					'description' => esc_html__( 'One CSS selector per line, e.g. #content or .site-bg. Manual fallback for wallpapers the automatic detection misses; elements are kept visible during the animation. Only used while the transparent backdrop is on.', 'swarm-rebuild' ),
				),
				'mobile_enabled' => array(
					'label'       => esc_html__( 'Enable on mobile', 'swarm-rebuild' ),
					'type'        => 'checkbox',
					'description' => esc_html__( 'Off by default: touch devices have no cursor metaphor.', 'swarm-rebuild' ),
				),
			);
		}

		public static function render_section_intro() {
			echo '<p>' . esc_html__( 'The rebuild plays automatically on every visit to pages in scope — finished elements become clickable while the swarm is still building. Visitors who dismissed the animation (Esc or the card) keep that choice in their browser.', 'swarm-rebuild' ) . '</p>';
			echo '<p>' . esc_html__( 'Settings travel to visitors inside the page HTML: saving automatically purges common page caches (WP Super Cache, W3 Total Cache, WP Rocket, LiteSpeed). If a CDN caches full HTML on top of those, refresh it there as well.', 'swarm-rebuild' ) . '</p>';
		}

		public static function render_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			if ( isset( $_GET['settings-updated'] ) ) {
				settings_errors();
			}
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Swarm Rebuild', 'swarm-rebuild' ); ?></h1>
				<form method="post" action="options.php">
					<?php settings_fields( self::OPTION_GROUP ); ?>
					<?php do_settings_sections( self::PAGE_SLUG ); ?>
					<?php submit_button(); ?>
				</form>
				<script>
				(function () {
					var select = document.getElementById('sr_scope');
					var row = document.getElementById('sr_scope_urls') ? document.getElementById('sr_scope_urls').closest('tr') : null;
					if (!select || !row) return;
					function sync() { row.style.display = select.value === 'urls' ? '' : 'none'; }
					select.addEventListener('change', sync);
					sync();
				})();
				</script>
			</div>
			<?php
		}

		/**
		 * Single renderer for all field types.
		 *
		 * @param array $args { 'field' => registry entry, 'key' => settings key }.
		 */
		public static function render_field( $args ) {
			$key    = $args['key'];
			$field  = $args['field'];
			$values = SR_Settings::get();
			$name   = SR_Settings::OPTION_KEY . '[' . $key . ']';

			if ( 'select' === $field['type'] ) {
				echo '<select id="sr_' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '">';
				foreach ( $field['choices'] as $value => $label ) {
					echo '<option value="' . esc_attr( $value ) . '"' . selected( $values[ $key ], $value, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select>';
			} elseif ( 'checkbox' === $field['type'] ) {
				echo '<label><input type="checkbox" id="sr_' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="1"' . checked( ! empty( $values[ $key ] ), true, false ) . '> ' . esc_html__( 'Enabled', 'swarm-rebuild' ) . '</label>';
			} elseif ( 'textarea' === $field['type'] ) {
				echo '<textarea id="sr_' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" rows="4" cols="50">' . esc_textarea( implode( "\n", (array) $values[ $key ] ) ) . '</textarea>';
			} elseif ( 'number' === $field['type'] ) {
				echo '<input type="number" id="sr_' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $values[ $key ] ) . '"'
					. ' min="' . esc_attr( $field['min'] ) . '" max="' . esc_attr( $field['max'] ) . '" step="' . esc_attr( $field['step'] ) . '">';
			}

			if ( ! empty( $field['description'] ) ) {
				echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
			}

			if ( 'wallpaper_selectors' === $key ) {
				self::render_selector_hint();
			}
		}

		/**
		 * Collapsible help for the wallpaper-selectors field: examples, how to
		 * find the selector, and a console snippet that lists candidates.
		 */
		private static function render_selector_hint() {
			$snippet = '[...document.querySelectorAll(\'body *\')].filter(el => {' . "\n"
				. '  const cs = getComputedStyle(el), r = el.getBoundingClientRect();' . "\n"
				. '  return cs.position === \'fixed\' && r.width >= innerWidth * .9 && r.height >= innerHeight * .9' . "\n"
				. '    && cs.backgroundImage !== \'none\' || [\'CANVAS\',\'IMG\',\'VIDEO\'].includes(el.tagName) && cs.position === \'fixed\';' . "\n"
				. '}).map(el => (el.id ? \'#\' + el.id : \'.\' + String(el.className).split(\' \')[0]) + \'  <\' + el.tagName + \'>\')';
			echo '<details class="sr-selector-hint" style="margin-top:6px">';
			echo '<summary style="cursor:pointer">' . esc_html__( 'How do I find the selector?', 'swarm-rebuild' ) . '</summary>';
			echo '<p class="description">' . esc_html( __( 'Examples: #content (Argon-style pseudo-element wallpapers), .site-bg (dedicated background div), #background-video (video wallpaper wrapper), canvas.wallpaper (class-named fullscreen canvas).', 'swarm-rebuild' ) ) . '</p>';
			echo '<p class="description">' . esc_html( __( 'Find it: open your site, press F12, pick the inspector arrow and click the wallpaper — the highlighted element\'s id or first class is your selector. If it cannot be clicked (pseudo-element layers), paste the snippet below into the browser console to list candidates, then fill one per line.', 'swarm-rebuild' ) ) . '</p>';
			echo '<p><code style="display:block;white-space:pre;padding:8px;background:#f6f7f7;max-width:640px;overflow-x:auto">' . esc_html( $snippet ) . '</code></p>';
			echo '<p class="description">' . esc_html( __( 'Do not list plain content containers that carry no wallpaper (harmless but useless). If a theme still misbehaves, turn off "Transparent backdrop" — it always uses a safe solid-color wipe.', 'swarm-rebuild' ) ) . '</p>';
			echo '</details>';
		}
	}
}
