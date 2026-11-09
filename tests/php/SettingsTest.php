<?php

use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['__sr_options'] = array();
	}

	public function test_defaults_returns_verbatim_keys_and_values() {
		$expected = array(
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
		$this->assertSame( $expected, SR_Settings::defaults() );
	}

	public function test_sanitize_whitelists_canvas_bg() {
		$this->assertSame( 'transparent', SR_Settings::sanitize( array( 'canvas_bg' => 'transparent' ) )['canvas_bg'] );
		$this->assertSame( 'white', SR_Settings::sanitize( array( 'canvas_bg' => 'white' ) )['canvas_bg'] );
		$this->assertSame( 'page', SR_Settings::sanitize( array( 'canvas_bg' => 'evil' ) )['canvas_bg'] );
	}

	public function test_get_returns_defaults_when_no_option_stored() {
		$this->assertSame( SR_Settings::defaults(), SR_Settings::get() );
	}

	public function test_get_merges_stored_partial_over_defaults() {
		$GLOBALS['__sr_options']['sr_settings'] = array( 'scope' => 'site', 'intro_enabled' => true );
		$merged = SR_Settings::get();
		$this->assertSame( 'site', $merged['scope'] );
		$this->assertTrue( $merged['intro_enabled'] );
		$this->assertTrue( $merged['card_enabled'] );
		$this->assertSame( 2.5, $merged['intro_delay'] );
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
			'intro_enabled'  => '1',
			'card_enabled'   => '',
			'sound_default'  => '1',
			'hotkey_enabled' => '1',
		);
		$out = SR_Settings::sanitize( $in );
		$this->assertTrue( $out['intro_enabled'] );
		$this->assertFalse( $out['card_enabled'] );
		$this->assertTrue( $out['sound_default'] );
		$this->assertTrue( $out['hotkey_enabled'] );
		$this->assertFalse( $out['mobile_enabled'] ); // absent → false
	}

	public function test_sanitize_keeps_delay_in_range_and_falls_back_otherwise() {
		$this->assertSame( 3.2, SR_Settings::sanitize( array( 'intro_delay' => '3.2' ) )['intro_delay'] );
		$this->assertSame( 2.5, SR_Settings::sanitize( array( 'intro_delay' => '999' ) )['intro_delay'] );
		$this->assertSame( 2.5, SR_Settings::sanitize( array( 'intro_delay' => '0' ) )['intro_delay'] );
		$this->assertSame( 2.5, SR_Settings::sanitize( array( 'intro_delay' => 'abc' ) )['intro_delay'] );
		$this->assertSame( 0.5, SR_Settings::sanitize( array( 'intro_delay' => '0.5' ) )['intro_delay'] );
		$this->assertSame( 10.0, SR_Settings::sanitize( array( 'intro_delay' => '10' ) )['intro_delay'] );
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
