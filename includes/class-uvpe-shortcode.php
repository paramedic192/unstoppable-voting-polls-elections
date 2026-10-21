<?php
/**
 * Shortcode Handler.
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the [uvpe_poll] shortcode.
 */
class UVPE_Shortcode {

	/**
	 * Hook into WordPress.
	 */
	public function __construct() {
		add_shortcode( 'uvpe_poll', array( $this, 'render_poll' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_assets() {
		wp_register_style(
			'uvpe-frontend',
			UVPE_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			UVPE_VERSION
		);

		wp_register_script(
			'uvpe-frontend',
			UVPE_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			UVPE_VERSION,
			true
		);

		wp_localize_script(
			'uvpe-frontend',
			'uvpe_frontend',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'i18n'     => array(
					'submitting'   => __( 'Submitting…', 'unstoppable-voting-polls-elections' ),
					'generic'      => __( 'Could not submit your vote. Please try again.', 'unstoppable-voting-polls-elections' ),
					'no_selection' => __( 'Please select an option.', 'unstoppable-voting-polls-elections' ),
				),
			)
		);
	}

	/**
	 * Render the [uvpe_poll id="123"] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_poll( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'uvpe_poll'
		);

		$poll_id = absint( $atts['id'] );
		if ( $poll_id <= 0 ) {
			return '<!-- Unstoppable Voting, Polls & Elections: missing or invalid poll id -->';
		}

		$poll = UVPE_Poll::get( $poll_id );
		if ( ! $poll ) {
			return '<!-- Unstoppable Voting, Polls & Elections: poll not found -->';
		}

		// Hide draft polls from the public.
		if ( 'draft' === $poll->status && ! current_user_can( 'manage_options' ) ) {
			return '';
		}

		wp_enqueue_style( 'uvpe-frontend' );
		wp_enqueue_script( 'uvpe-frontend' );

		return UVPE_Templates::render( $poll );
	}
}
