<?php
/**
 * Uninstall cleanup: the plugin stores one option and nothing else — no user
 * meta, no tables, no cron events (zero telemetry by design).
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'sr_settings' );
