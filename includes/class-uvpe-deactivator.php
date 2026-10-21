<?php
/**
 * Plugin Deactivator.
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs on plugin deactivation.
 */
class UVPE_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Note: We do NOT drop tables here — that happens in uninstall.php
	 * so users don't lose their poll data when toggling the plugin off.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
