<?php

use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['__sr_options'] = array();
	}

	public function test_defaults_returns_verbatim_keys_and_values() {
		$expected = array(
			'scope'                => 'home',
			'scope_urls'           => array(),
			'auto_return'          => true,
			'sound_default'        => true,
			'tour_default'         => true,
			'transparent_backdrop' => true,
			'wallpaper_selectors'  => array(),
			'mobile_enabled'       => false,
		);
		$this->assertSame( $expected, SR_Settings::defaults() );
	}

	public function test_get_returns_defaults_when_no_option_stored() {
		$this->assertSame( SR_Settings::defaults(), SR_Settings::get() );
	}

	public function test_get_merges_stored_partial_over_defaults() {
		$GLOBALS['__sr_options']['sr_settings'] = array( 'scope' => 'site', 'auto_return' => false );
		$merged = SR_Settings::get();
		$this->assertSame( 'site', $merged['scope'] );
		$this->assertFalse( $merged['auto_return'] );
		$this->assertTrue( $merged['sound_default'] );
	}

	public function test_sanitize_whitelists_scope() {
		$out = SR_Settings::sanitize( array( 'scope' => 'evil' ) );
		$this->assertSame( 'home', $out['scope'] );
		$out = SR_Settings::sanitize( array( 'scope' => 'urls' ) );
		$this->assertSame( 'urls', $out['scope'] );
	}

	public function test_sanitize_normalizes_scope_urls() {
		$in = array(
			'scope'      => 'urls',
			'scope_urls' => array( ' /about/ ', 'https://example.com/contact/', '', '/pricing' ),
		);
		$out = SR_Settings::sanitize( $in );
		$this->assertSame( array( 'about', 'contact', 'pricing' ), $out['scope_urls'] );
	}

	/**
	 * The Settings API delivers the textarea as a newline-separated STRING,
	 * not an array — the real form shape, not the internal shape (review C1).
	 */
	public function test_sanitize_accepts_scope_urls_as_textarea_string() {
		$in = array(
			'scope'      => 'urls',
			'scope_urls' => " /about/ \r\nhttps://example.com/contact/\n\n/pricing",
		);
		$out = SR_Settings::sanitize( $in );
		$this->assertSame( array( 'about', 'contact', 'pricing' ), $out['scope_urls'] );
	}

	public function test_sanitize_casts_booleans_and_missing_flags_to_false() {
		$in = array(
			'auto_return'          => '1',
			'sound_default'        => '1',
			'tour_default'         => '1',
			'transparent_backdrop' => '1',
		);
		$out = SR_Settings::sanitize( $in );
		$this->assertTrue( $out['auto_return'] );
		$this->assertTrue( $out['sound_default'] );
		$this->assertTrue( $out['tour_default'] );
		$this->assertTrue( $out['transparent_backdrop'] );
		$this->assertFalse( $out['mobile_enabled'] ); // absent → false
	}

	public function test_sanitize_accepts_wallpaper_selectors_as_textarea_string() {
		$in = array(
			'wallpaper_selectors' => " #content \r\n<script>.evil</script>.site-bg\n\n.body-bg\r\n#one\r\n#two\r\n#three\r\n#four\r\n#five\r\n#six",
		);
		$out = SR_Settings::sanitize( $in );
		$this->assertSame( array( '#content', '.site-bg', '.body-bg', '#one', '#two' ), $out['wallpaper_selectors'] );
	}

	public function test_sanitize_caps_wallpaper_selectors_length_and_drops_empty() {
		$out = SR_Settings::sanitize( array( 'wallpaper_selectors' => array( '  ', str_repeat( 'a', 300 ) ) ) );
		$this->assertSame( array( str_repeat( 'a', 200 ) ), $out['wallpaper_selectors'] );
	}

	public function test_sanitize_drops_legacy_keys_from_older_versions() {
		// A stored form from 0.1.x may still post intro/card/hotkey/delay keys.
		$in = array(
			'scope'          => 'home',
			'intro_enabled'  => '1',
			'card_enabled'   => '1',
			'hotkey_enabled' => '1',
			'intro_delay'    => '4',
		);
		$out = SR_Settings::sanitize( $in );
		$this->assertSame( array( 'scope', 'scope_urls', 'auto_return', 'sound_default', 'tour_default', 'transparent_backdrop', 'wallpaper_selectors', 'mobile_enabled' ), array_keys( $out ) );
	}

	public function test_sanitize_output_key_set_equals_defaults_key_set() {
		$out = SR_Settings::sanitize( array() );
		$this->assertSame( array_keys( SR_Settings::defaults() ), array_keys( $out ) );
	}

	public function test_sanitize_handles_null_input() {
		$out = SR_Settings::sanitize( null );
		$this->assertSame( SR_Settings::defaults(), $out );
	}
}
