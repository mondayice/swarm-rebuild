<?php
/**
 * Plugin Name:       Swarm Rebuild
 * Description:       Wipe the page to a blank canvas and let a swarm of multiplayer cursors rebuild it live — crosshair boxes, typed text, pen lines, ruler guides.
 * Version:           0.1.1
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Swarm Rebuild contributors
 * License:           GPL-2.0-or-later
 * Text Domain:       swarm-rebuild
 */

// Direct access guard.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SWARM_REBUILD_VERSION', '0.1.1' );
define( 'SWARM_REBUILD_FILE', __FILE__ );
define( 'SWARM_REBUILD_DIR', plugin_dir_path( __FILE__ ) );
define( 'SWARM_REBUILD_URL', plugin_dir_url( __FILE__ ) );

require_once SWARM_REBUILD_DIR . 'includes/class-sr-settings.php';
require_once SWARM_REBUILD_DIR . 'includes/class-sr-admin.php';
require_once SWARM_REBUILD_DIR . 'includes/class-sr-frontend.php';

if ( is_admin() ) {
	add_action( 'admin_menu', array( 'SR_Admin', 'register_menu' ) );
	add_action( 'admin_init', array( 'SR_Admin', 'register_settings' ) );
} else {
	add_action( 'init', array( 'SR_Frontend', 'register' ) );
	add_action( 'wp_enqueue_scripts', array( 'SR_Frontend', 'enqueue_assets' ) );
}
