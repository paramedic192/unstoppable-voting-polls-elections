<?php
/**
 * Vote Handler.
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles vote casting and duplicate-vote checks.
 */
class UVPE_Vote {

	const COOKIE_PREFIX = 'uvpe_voted_';

	/**
	 * Cast one or more votes for a poll.
	 *
	 * Performs:
	 *  - poll-exists check
	 *  - poll-open check
	 *  - guest-allowed check
	 *  - duplicate-vote check (cookie / IP / user)
	 *  - max_choices enforcement for multi-choice polls
	 *  - answer-belongs-to-poll check
	 *
	 * @param int   $poll_id     Poll ID.
	 * @param int[] $answer_ids  Selected answer IDs.
	 * @return true|WP_Error
	 */
	public static function cast( $poll_id, $answer_ids ) {
		global $wpdb;

		$poll_id = absint( $poll_id );
		$poll    = UVPE_Poll::get( $poll_id );
		if ( ! $poll ) {
			return new WP_Error( 'no_poll', __( 'This poll does not exist.', 'unstoppable-voting-polls-elections' ) );
		}

		if ( ! UVPE_Poll::is_open( $poll ) ) {
			return new WP_Error( 'poll_closed', __( 'This poll is not currently accepting votes.', 'unstoppable-voting-polls-elections' ) );
		}

		// Guest check.
		if ( ! is_user_logged_in() && ! (int) $poll->allow_guests ) {
			return new WP_Error( 'login_required', __( 'You must be logged in to vote in this poll.', 'unstoppable-voting-polls-elections' ) );
		}

		// Validate selections.
		$answer_ids = array_values( array_unique( array_map( 'absint', (array) $answer_ids ) ) );
		$answer_ids = array_filter( $answer_ids, function ( $id ) { return $id > 0; } );

		if ( empty( $answer_ids ) ) {
			return new WP_Error( 'no_answer', __( 'Please select at least one option.', 'unstoppable-voting-polls-elections' ) );
		}

		if ( 'single' === $poll->poll_type ) {
			if ( count( $answer_ids ) > 1 ) {
				return new WP_Error( 'too_many', __( 'This poll only allows one selection.', 'unstoppable-voting-polls-elections' ) );
			}
		} else {
			$max = max( 1, (int) $poll->max_choices );
			if ( count( $answer_ids ) > $max ) {
				return new WP_Error(
					'too_many',
					sprintf(
						/* translators: %d: max number of choices allowed. */
						__( 'You may select up to %d options.', 'unstoppable-voting-polls-elections' ),
						$max
					)
				);
			}
		}

		// Confirm every selected answer actually belongs to this poll.
		$valid_ids = UVPE_Poll::get_answer_ids( $poll_id );
		foreach ( $answer_ids as $aid ) {
			if ( ! in_array( $aid, $valid_ids, true ) ) {
				return new WP_Error( 'bad_answer', __( 'One of the selected options is not valid for this poll.', 'unstoppable-voting-polls-elections' ) );
			}
		}

		// Duplicate-vote check.
		if ( self::has_already_voted( $poll ) ) {
			return new WP_Error( 'already_voted', __( 'You have already voted in this poll.', 'unstoppable-voting-polls-elections' ) );
		}

		// All checks passed — record the votes.
		$now         = current_time( 'mysql' );
		$user_id     = get_current_user_id();
		$ip          = self::get_client_ip();
		$tracking_id = self::get_or_set_tracking_id();
		$votes_table = UVPE_Schema::table( 'votes' );

		foreach ( $answer_ids as $aid ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->insert(
				$votes_table,
				array(
					'poll_id'     => $poll_id,
					'answer_id'   => $aid,
					'user_id'     => $user_id,
					'ip_address'  => $ip,
					'tracking_id' => $tracking_id,
					'added_date'  => $now,
				),
				array( '%d', '%d', '%d', '%s', '%s', '%s' )
			);
		}

		// Set the cookie marker.
		self::mark_voted_cookie( $poll_id );

		// Recalculate counts.
		UVPE_Poll::recalc_answer_votes( $poll_id );
		UVPE_Poll::recalc_total_votes( $poll_id );

		return true;
	}

	/**
	 * Determine whether the current visitor has already voted in the poll.
	 *
	 * @param object $poll Poll row.
	 * @return bool
	 */
	public static function has_already_voted( $poll ) {
		if ( ! $poll ) {
			return false;
		}

		$block = isset( $poll->block_by ) ? $poll->block_by : 'cookie';

		// Always block authenticated users from voting twice.
		if ( is_user_logged_in() ) {
			if ( self::user_has_voted( $poll->id, get_current_user_id() ) ) {
				return true;
			}
		}

		switch ( $block ) {
			case 'none':
				// Only the user-based check above applies.
				return false;

			case 'ip':
				return self::ip_has_voted( $poll->id, self::get_client_ip() );

			case 'both':
				if ( self::ip_has_voted( $poll->id, self::get_client_ip() ) ) {
					return true;
				}
				return self::cookie_has_voted( $poll->id );

			case 'cookie':
			default:
				return self::cookie_has_voted( $poll->id );
		}
	}

	/**
	 * Check if a particular user has already voted in a poll.
	 *
	 * @param int $poll_id Poll ID.
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private static function user_has_voted( $poll_id, $user_id ) {
		global $wpdb;
		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return false;
		}
		$table = UVPE_Schema::table( 'votes' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table identifier built from $wpdb->prefix; values bound via prepare().
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE poll_id = %d AND user_id = %d", absint( $poll_id ), $user_id ) );
		return $count > 0;
	}

	/**
	 * Check if an IP has already voted in a poll.
	 *
	 * @param int    $poll_id Poll ID.
	 * @param string $ip      Visitor IP.
	 * @return bool
	 */
	private static function ip_has_voted( $poll_id, $ip ) {
		global $wpdb;
		if ( empty( $ip ) || '0.0.0.0' === $ip ) {
			return false;
		}
		$table = UVPE_Schema::table( 'votes' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table identifier built from $wpdb->prefix; values bound via prepare().
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE poll_id = %d AND ip_address = %s", absint( $poll_id ), $ip ) );
		return $count > 0;
	}

	/**
	 * Cookie-based duplicate check.
	 *
	 * @param int $poll_id Poll ID.
	 * @return bool
	 */
	private static function cookie_has_voted( $poll_id ) {
		$cookie_name = self::COOKIE_PREFIX . absint( $poll_id );
		return isset( $_COOKIE[ $cookie_name ] ) && '1' === sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
	}

	/**
	 * Set the "you voted" cookie for a poll. 30-day expiration.
	 *
	 * @param int $poll_id Poll ID.
	 */
	private static function mark_voted_cookie( $poll_id ) {
		$cookie_name = self::COOKIE_PREFIX . absint( $poll_id );
		// Do not call setcookie() if headers are already sent (e.g. during AJAX past the headers).
		if ( ! headers_sent() ) {
			$secure = is_ssl();
			setcookie( $cookie_name, '1', time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, true );
		}
	}

	/**
	 * Get a tracking ID for this visitor, generating one if needed.
	 *
	 * This isn't used for security decisions — just for record-keeping.
	 *
	 * @return string
	 */
	private static function get_or_set_tracking_id() {
		if ( isset( $_COOKIE['uvpe_tracking'] ) ) {
			$existing = sanitize_text_field( wp_unslash( $_COOKIE['uvpe_tracking'] ) );
			if ( strlen( $existing ) >= 16 && strlen( $existing ) <= 64 ) {
				return $existing;
			}
		}

		$tracking_id = wp_generate_password( 32, false, false );

		if ( ! headers_sent() ) {
			$secure = is_ssl();
			setcookie( 'uvpe_tracking', $tracking_id, time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, true );
		}
		return $tracking_id;
	}

	/**
	 * Get the visitor's IP. NOT used as security input — only as a soft
	 * duplicate-vote signal.
	 *
	 * @return string
	 */
	private static function get_client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( '' === $ip ) {
			return '0.0.0.0';
		}
		// Validate it's actually an IP.
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return '0.0.0.0';
		}
		return $ip;
	}
}
