<?php
/**
 * Admin settings page via Settings API.
 *
 * One page under Settings, one section, eight fields driven by a field
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
				'intro_enabled'  => array(
					'label'       => esc_html__( 'Intro mode', 'swarm-rebuild' ),
					'type'        => 'checkbox',
					'description' => esc_html__( 'Auto-play the rebuild once per visitor on pages in scope. Skippable and respects reduced-motion.', 'swarm-rebuild' ),
				),
				'card_enabled'   => array(
					'label'       => esc_html__( 'Visitor control card', 'swarm-rebuild' ),
					'type'        => 'checkbox',
					'description' => esc_html__( 'Floating card in the bottom-left corner with replay, stop and sound controls.', 'swarm-rebuild' ),
				),
				'sound_default'  => array(
					'label' => esc_html__( 'Sound on by default', 'swarm-rebuild' ),
					'type'  => 'checkbox',
				),
				'hotkey_enabled' => array(
					'label'       => esc_html__( 'Keyboard shortcut', 'swarm-rebuild' ),
					'type'        => 'checkbox',
					'description' => esc_html__( 'Alt+Shift+S triggers the rebuild on pages in scope.', 'swarm-rebuild' ),
				),
				'mobile_enabled' => array(
					'label'       => esc_html__( 'Enable on mobile', 'swarm-rebuild' ),
					'type'        => 'checkbox',
					'description' => esc_html__( 'Off by default: touch devices have no cursor metaphor.', 'swarm-rebuild' ),
				),
				'intro_delay'    => array(
					'label'       => esc_html__( 'Intro end delay (seconds)', 'swarm-rebuild' ),
					'type'        => 'number',
					'min'         => 0.5,
					'max'         => 10,
					'step'        => 0.5,
					'description' => esc_html__( 'How long the finished state lingers before auto-returning to the live page.', 'swarm-rebuild' ),
				),
			);
		}

		public static function render_section_intro() {
			echo '<p>' . esc_html__( 'Choose where the swarm can run and how visitors experience it. Visitors who turned the animation off in their browser keep that choice.', 'swarm-rebuild' ) . '</p>';
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
		}
	}
}
