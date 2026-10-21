<?php
/**
 * Plugin Activator.
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs on plugin activation.
 */
class UVPE_Activator {

	/**
	 * Activate the plugin.
	 */
	public static function activate() {
		UVPE_Schema::create_tables();

		// Record install date for review nag timing.
		if ( ! get_option( 'uvpe_install_date' ) ) {
			update_option( 'uvpe_install_date', time() );
		}

		// Default settings.
		if ( false === get_option( 'uvpe_settings' ) ) {
			update_option( 'uvpe_settings', array(
				'default_template'   => 'classic',
				'default_sort_order' => 'manual',
				'brand_color'        => '#f0b849',
			) );
		}

		flush_rewrite_rules();
	}
}
