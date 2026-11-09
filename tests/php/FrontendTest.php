<?php

use PHPUnit\Framework\TestCase;

class FrontendTest extends TestCase {

	private $s;

	protected function setUp(): void {
		$GLOBALS['__sr_options']        = array();
		$GLOBALS['__sr_registered_tag'] = null;
		$GLOBALS['__sr_post_meta']      = array();
		$GLOBALS['__sr_actions']        = array();
		$GLOBALS['__sr_enqueued']       = array();
		$this->s = SR_Settings::defaults();
	}

	/**
	 * Root cause of the "not working on a real site" report (Task 9):
	 * print_config used to be added to wp_head at priority 1 from INSIDE
	 * enqueue_assets — but wp_enqueue_scripts itself runs on wp_head at
	 * priority 1, and a callback added to a priority that is mid-execution
	 * never runs that round (WP_Hook iterates a snapshot). Registration must
	 * happen ahead of time, at a priority other than 1.
	 */
	public function test_print_config_registered_on_wp_head_away_from_enqueue_priority() {
		SR_Frontend::register();
		$found = array();
		foreach ( $GLOBALS['__sr_actions'] as list( $tag, $cb, $prio ) ) {
			if ( 'wp_head' === $tag && is_array( $cb ) && 'SR_Frontend' === $cb[0] && 'print_config' === $cb[1] ) {
				$found[] = $prio;
			}
		}
		$this->assertNotEmpty( $found, 'print_config must be registered on wp_head' );
		$this->assertNotContains( 1, $found, 'priority 1 collides with the running wp_enqueue_scripts callback' );
	}

	public function test_enqueue_assets_adds_no_runtime_hooks() {
		$GLOBALS['__sr_options']['sr_settings'] = array( 'scope' => 'site' );
		SR_Frontend::enqueue_assets();
		$this->assertSame( array(), $GLOBALS['__sr_actions'], 'enqueue_assets must not add_action while wp_head priority 1 is executing' );
		$this->assertNotEmpty( $GLOBALS['__sr_enqueued'] );
	}

	/* ---- is_in_scope ---- */

	public function test_scope_site_is_always_in_scope() {
		$this->assertTrue( SR_Frontend::is_in_scope( array_merge( $this->s, array( 'scope' => 'site' ) ), '/anything/', false ) );
	}

	public function test_scope_home_only_front_page() {
		$home = array_merge( $this->s, array( 'scope' => 'home' ) );
		$this->assertTrue( SR_Frontend::is_in_scope( $home, '/', true ) );
		$this->assertFalse( SR_Frontend::is_in_scope( $home, '/about/', false ) );
	}

	public function test_scope_urls_matches_after_slash_normalization() {
		$urls = array_merge( $this->s, array( 'scope' => 'urls', 'scope_urls' => array( 'about', 'pricing' ) ) );
		$this->assertTrue( SR_Frontend::is_in_scope( $urls, '/about/', false ) );
		$this->assertTrue( SR_Frontend::is_in_scope( $urls, '/pricing', false ) );
		$this->assertFalse( SR_Frontend::is_in_scope( $urls, '/about/team/', false ) );
		$this->assertFalse( SR_Frontend::is_in_scope( $urls, '/', false ) );
	}

	/* ---- is_mobile_request ---- */

	public function test_mobile_detection_by_user_agent() {
		$this->assertTrue( SR_Frontend::is_mobile_request( 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15' ) );
		$this->assertTrue( SR_Frontend::is_mobile_request( 'Mozilla/5.0 (Linux; Android 14; Pixel 8)' ) );
		$this->assertFalse( SR_Frontend::is_mobile_request( 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126.0 Safari/537.36' ) );
		$this->assertFalse( SR_Frontend::is_mobile_request( '' ) );
	}

	/* ---- should_enqueue ---- */

	public function test_mobile_gate_shortcircuits_everything_including_shortcode() {
		$s   = array_merge( $this->s, array( 'mobile_enabled' => false ) );
		$ctx = array( 'is_home' => true, 'path' => '/', 'ua' => 'Android phone UA', 'shortcode_active' => true );
		$this->assertFalse( SR_Frontend::should_enqueue( $s, $ctx ) );
	}

	public function test_mobile_gate_passes_when_enabled() {
		$s   = array_merge( $this->s, array( 'mobile_enabled' => true, 'scope' => 'site' ) );
		$ctx = array( 'is_home' => false, 'path' => '/x/', 'ua' => 'iPhone UA', 'shortcode_active' => false );
		$this->assertTrue( SR_Frontend::should_enqueue( $s, $ctx ) );
	}

	public function test_shortcode_active_overrides_scope() {
		$s   = array_merge( $this->s, array( 'scope' => 'home' ) );
		$ctx = array( 'is_home' => false, 'path' => '/deep/page/', 'ua' => 'desktop UA', 'shortcode_active' => true );
		$this->assertTrue( SR_Frontend::should_enqueue( $s, $ctx ) );
	}

	public function test_out_of_scope_desktop_page_does_not_enqueue() {
		$s   = array_merge( $this->s, array( 'scope' => 'home' ) );
		$ctx = array( 'is_home' => false, 'path' => '/blog/post/', 'ua' => 'desktop UA', 'shortcode_active' => false );
		$this->assertFalse( SR_Frontend::should_enqueue( $s, $ctx ) );
	}

	/* ---- is_truthy_attr ---- */

	public function test_truthy_shortcode_attr_variants() {
		$this->assertTrue( SR_Frontend::is_truthy_attr( 'true' ) );
		$this->assertTrue( SR_Frontend::is_truthy_attr( 'TRUE' ) );
		$this->assertTrue( SR_Frontend::is_truthy_attr( '1' ) );
		$this->assertTrue( SR_Frontend::is_truthy_attr( 'yes' ) );
		$this->assertTrue( SR_Frontend::is_truthy_attr( true ) );
		$this->assertFalse( SR_Frontend::is_truthy_attr( 'false' ) );
		$this->assertFalse( SR_Frontend::is_truthy_attr( '0' ) );
		$this->assertFalse( SR_Frontend::is_truthy_attr( '' ) );
		$this->assertFalse( SR_Frontend::is_truthy_attr( null ) );
	}

	/* ---- shortcode ---- */

	public function test_register_hooks_shortcode_with_tag() {
		SR_Frontend::register();
		$this->assertSame( 'swarm_rebuild', $GLOBALS['__sr_registered_tag'] );
	}

	public function test_shortcode_renders_hidden_marker_tag_with_autoplay_flag() {
		$out = SR_Frontend::render_shortcode( array( 'autoplay' => 'TRUE' ) );
		$this->assertStringContainsString( 'id="swarm-rebuild-shortcode"', $out );
		$this->assertStringContainsString( '"autoplay":true', $out );

		$out2 = SR_Frontend::render_shortcode( array() );
		$this->assertStringContainsString( '"autoplay":false', $out2 );
	}

	/* ---- build_config ---- */

	public function test_build_config_autoplay_is_simply_in_scope() {
		$ctx  = array( 'is_home' => true, 'path' => '/' );
		$cfg  = SR_Frontend::build_config( $this->s, $ctx );
		$this->assertTrue( $cfg['autoplay'] );
		$this->assertTrue( $cfg['scopeActive'] );
	}

	public function test_build_config_autoplay_out_of_scope() {
		$ctx  = array( 'is_home' => false, 'path' => '/other/' );
		$cfg  = SR_Frontend::build_config( $this->s, $ctx );
		$this->assertFalse( $cfg['autoplay'] );
	}

	public function test_build_config_shortcode_page_is_autoplay() {
		$ctx  = array( 'is_home' => false, 'path' => '/deep/', 'shortcode_active' => true );
		$cfg  = SR_Frontend::build_config( $this->s, $ctx );
		$this->assertTrue( $cfg['autoplay'] );
	}

	public function test_build_config_passes_through_settings_and_engine_url() {
		$s    = array_merge( $this->s, array( 'auto_return' => false, 'sound_default' => false ) );
		$cfg  = SR_Frontend::build_config( $s, array( 'is_home' => true, 'path' => '/' ) );
		$this->assertTrue( $cfg['scopeActive'] );
		$this->assertFalse( $cfg['autoReturn'] );
		$this->assertFalse( $cfg['soundDefault'] );
		$this->assertSame(
			SWARM_REBUILD_URL . 'assets/js/engine/swarm.js?ver=' . SWARM_REBUILD_VERSION,
			$cfg['engineUrl']
		);
	}

	public function test_build_config_strings_covers_all_14_keys() {
		$cfg      = SR_Frontend::build_config( $this->s, array( 'is_home' => true, 'path' => '/' ) );
		$expected = array(
			'scanning', 'agentsReady', 'rebuiltIn', 'sizeBadge', 'scrollHint',
			'cardReplay', 'cardClose', 'cardSound', 'cardSoundHint',
			'cardTour', 'cardTouring', 'cardTourHint', 'cardHide', 'cardShow',
		);
		$this->assertSame( $expected, array_keys( $cfg['strings'] ) );
		$this->assertSame( 'Rebuilt by <b>%d</b> agents in <b>%d</b>s', $cfg['strings']['rebuiltIn'] );
		$this->assertSame( '<b>%d</b> agents ready', $cfg['strings']['agentsReady'] );
		$this->assertSame( 'Auto-scroll while the swarm builds', $cfg['strings']['cardTourHint'] );
	}

	/**
	 * Review I4: the engine URL carries the plugin version so a cached loader
	 * after an update never pairs with a stale engine URL.
	 */
	public function test_build_config_engine_url_is_versioned() {
		$cfg = SR_Frontend::build_config( $this->s, array( 'is_home' => true, 'path' => '/' ) );
		$this->assertSame(
			SWARM_REBUILD_URL . 'assets/js/engine/swarm.js?ver=' . SWARM_REBUILD_VERSION,
			$cfg['engineUrl']
		);
	}

	/* ---- shortcode detection (incl. builder meta, review I3) ---- */

	public function test_detect_shortcode_in_post_content() {
		$post          = new stdClass();
		$post->ID      = 7;
		$post->post_content = 'Hello [swarm_rebuild autoplay="true"] world';
		$this->assertTrue( SR_Frontend::detect_shortcode( $post ) );
	}

	public function test_detect_shortcode_in_elementor_meta() {
		$GLOBALS['__sr_post_meta'] = array(
			7 => array( '_elementor_data' => array( '[{"shortcode":"[swarm_rebuild]"}]' ) ),
		);
		$post          = new stdClass();
		$post->ID      = 7;
		$post->post_content = '';
		$this->assertTrue( SR_Frontend::detect_shortcode( $post ) );
	}

	public function test_detect_shortcode_absent_everywhere() {
		$GLOBALS['__sr_post_meta'] = array();
		$post          = new stdClass();
		$post->ID      = 8;
		$post->post_content = 'Just content, no tag.';
		$this->assertFalse( SR_Frontend::detect_shortcode( $post ) );
	}
}
