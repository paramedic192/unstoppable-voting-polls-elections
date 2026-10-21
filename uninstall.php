<?php
/**
 * Uninstall handler.
 *
 * Fires only when the plugin is *deleted* (not deactivated).
 * Drops the plugin's database tables and removes its options.
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

// If uninstall.php is not called by WordPress, bail.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local uninstall script variables, not globals.

// Capability check — uninstall.php runs in WP's context so caps are available.
if ( ! current_user_can( 'activate_plugins' ) ) {
	exit;
}

// Plugin code isn't auto-loaded during uninstall — pull in what we need.
define( 'UVPE_TABLE_PREFIX', 'uvpe_' );
require_once plugin_dir_path( __FILE__ ) . 'includes/class-uvpe-schema.php';

// Drop tables.
UVPE_Schema::drop_tables();

// Delete options.
$options = array(
	'uvpe_install_date',
	'uvpe_settings',
	'uvpe_welcome_notice_dismissed',
	'uvpe_review_notice_dismissed',
);
foreach ( $options as $opt ) {
	delete_option( $opt );
}
