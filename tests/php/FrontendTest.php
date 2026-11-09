<?php

use PHPUnit\Framework\TestCase;

class FrontendTest extends TestCase {

	private $s;

	protected function setUp(): void {
		$GLOBALS['__sr_options']        = array();
		$GLOBALS['__sr_registered_tag'] = null;
		$this->s = SR_Settings::defaults();
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

	public function test_build_config_intro_disabled_and_null_mode() {
		$ctx  = array( 'is_home' => true, 'path' => '/' );
		$cfg  = SR_Frontend::build_config( $this->s, $ctx );
		$this->assertFalse( $cfg['autoplay'] );
		$this->assertNull( $cfg['autoplayMode'] );
	}

	public function test_build_config_intro_enabled_and_in_scope() {
		$s    = array_merge( $this->s, array( 'intro_enabled' => true, 'scope' => 'home' ) );
		$ctx  = array( 'is_home' => true, 'path' => '/' );
		$cfg  = SR_Frontend::build_config( $s, $ctx );
		$this->assertTrue( $cfg['autoplay'] );
		$this->assertSame( 'intro', $cfg['autoplayMode'] );
	}

	public function test_build_config_intro_enabled_but_out_of_scope() {
		$s    = array_merge( $this->s, array( 'intro_enabled' => true, 'scope' => 'home' ) );
		$ctx  = array( 'is_home' => false, 'path' => '/other/' );
		$cfg  = SR_Frontend::build_config( $s, $ctx );
		$this->assertFalse( $cfg['autoplay'] );
		$this->assertNull( $cfg['autoplayMode'] );
	}

	public function test_build_config_passes_through_settings_and_engine_url() {
		$s    = array_merge( $this->s, array( 'intro_delay' => 4.5, 'hotkey_enabled' => false, 'sound_default' => false ) );
		$cfg  = SR_Frontend::build_config( $s, array( 'is_home' => true, 'path' => '/' ) );
		$this->assertTrue( $cfg['scopeActive'] );
		$this->assertSame( 4.5, $cfg['introDelay'] );
		$this->assertFalse( $cfg['hotkeyEnabled'] );
		$this->assertTrue( $cfg['cardEnabled'] );
		$this->assertFalse( $cfg['soundDefault'] );
		$this->assertSame( SWARM_REBUILD_URL . 'assets/js/engine/swarm.js', $cfg['engineUrl'] );
	}

	public function test_build_config_strings_covers_all_22_keys() {
		$cfg      = SR_Frontend::build_config( $this->s, array( 'is_home' => true, 'path' => '/' ) );
		$expected = array(
			'tour', 'touring', 'ttTour', 'ttSpeed', 'ttSound', 'ttLive', 'ttReplay', 'ttExit',
			'goLive', 'replay', 'exit', 'tapForSound', 'soundOn', 'soundOff',
			'agentsReady', 'rebuiltIn', 'sizeBadge',
			'cardReplay', 'cardClose', 'cardSound', 'cardHide', 'cardShow',
		);
		$this->assertSame( $expected, array_keys( $cfg['strings'] ) );
		$this->assertSame( 'Go live', $cfg['strings']['goLive'] );
		$this->assertSame( 'Rebuilt by <b>%d</b> agents in <b>%d</b>s', $cfg['strings']['rebuiltIn'] );
		$this->assertSame( '<b>%d</b> agents ready', $cfg['strings']['agentsReady'] );
		$this->assertSame( 'Auto-scroll while the swarm builds', $cfg['strings']['ttTour'] );
	}
}
