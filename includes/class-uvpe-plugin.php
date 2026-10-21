<?php
/**
 * Main Plugin Class.
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The plugin bootstrap — wires up subsystems.
 */
class UVPE_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var UVPE_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return UVPE_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire up the subsystems.
	 */
	private function __construct() {
		new UVPE_Shortcode();
		new UVPE_Ajax();

		if ( is_admin() ) {
			new UVPE_Admin();
		}
	}

	/**
	 * Don't allow cloning.
	 */
	private function __clone() {}

	/**
	 * Don't allow unserialising.
	 */
	public function __wakeup() {}
}
