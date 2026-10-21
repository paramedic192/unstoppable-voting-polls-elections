<?php
/**
 * AJAX Handlers.
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end and admin AJAX handlers.
 */
class UVPE_Ajax {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		// Public-facing vote submission.
		add_action( 'wp_ajax_uvpe_submit_vote', array( $this, 'submit_vote' ) );
		add_action( 'wp_ajax_nopriv_uvpe_submit_vote', array( $this, 'submit_vote' ) );

		// Admin operations.
		add_action( 'wp_ajax_uvpe_delete_poll', array( $this, 'admin_delete_poll' ) );
		add_action( 'wp_ajax_uvpe_dismiss_notice', array( $this, 'dismiss_notice' ) );
	}

	/**
	 * Handle a public vote submission.
	 */
	public function submit_vote() {
		$poll_id = isset( $_POST['poll_id'] ) ? absint( $_POST['poll_id'] ) : 0;

		if ( $poll_id <= 0 ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid poll.', 'unstoppable-voting-polls-elections' ),
			) );
		}

		// Nonce is tied to the specific poll ID.
		if ( ! isset( $_POST['uvpe_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['uvpe_nonce'] ) ), 'uvpe_vote_' . $poll_id ) ) {
			wp_send_json_error( array(
				'message' => __( 'Security check failed. Please refresh the page and try again.', 'unstoppable-voting-polls-elections' ),
			) );
		}

		// Sanitize answer IDs.
		$answer_ids = array();
		if ( isset( $_POST['answer_ids'] ) ) {
			$raw        = array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['answer_ids'] ) );
			$answer_ids = array_map( 'absint', $raw );
			$answer_ids = array_filter( $answer_ids, function ( $id ) { return $id > 0; } );
		}

		if ( empty( $answer_ids ) ) {
			wp_send_json_error( array(
				'message' => __( 'Please select an option.', 'unstoppable-voting-polls-elections' ),
			) );
		}

		$result = UVPE_Vote::cast( $poll_id, $answer_ids );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'message' => $result->get_error_message(),
			) );
		}

		// Vote saved — fetch the updated poll so we can re-render with results.
		$poll = UVPE_Poll::get( $poll_id );
		$html = UVPE_Templates::render( $poll );

		wp_send_json_success( array(
			'message' => __( 'Thanks for voting!', 'unstoppable-voting-polls-elections' ),
			'html'    => $html,
		) );
	}

	/**
	 * Admin: delete a poll.
	 */
	public function admin_delete_poll() {
		check_ajax_referer( 'uvpe_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'unstoppable-voting-polls-elections' ) ) );
		}

		$poll_id = isset( $_POST['poll_id'] ) ? absint( $_POST['poll_id'] ) : 0;
		if ( $poll_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid poll.', 'unstoppable-voting-polls-elections' ) ) );
		}

		$ok = UVPE_Poll::delete( $poll_id );
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => __( 'Could not delete poll.', 'unstoppable-voting-polls-elections' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Poll deleted.', 'unstoppable-voting-polls-elections' ) ) );
	}

	/**
	 * Admin: dismiss an admin notice.
	 */
	public function dismiss_notice() {
		check_ajax_referer( 'uvpe_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'unstoppable-voting-polls-elections' ) ) );
		}

		$notice = isset( $_POST['notice'] ) ? sanitize_key( wp_unslash( $_POST['notice'] ) ) : '';
		$allowed = array( 'welcome', 'review' );
		if ( ! in_array( $notice, $allowed, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid notice.', 'unstoppable-voting-polls-elections' ) ) );
		}

		update_option( 'uvpe_' . $notice . '_notice_dismissed', 1 );
		wp_send_json_success();
	}
}
