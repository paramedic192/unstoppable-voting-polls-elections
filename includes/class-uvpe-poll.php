<?php
/**
 * Poll Model / CRUD Handler.
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles poll and answer CRUD operations.
 */
class UVPE_Poll {

	/**
	 * Allowed templates.
	 *
	 * @return array
	 */
	public static function get_templates() {
		$templates = array(
			'classic'    => __( 'Classic Bars', 'unstoppable-voting-polls-elections' ),
			'ballot'     => __( 'Official Ballot', 'unstoppable-voting-polls-elections' ),
			'cards'      => __( 'Candidate Cards', 'unstoppable-voting-polls-elections' ),
			'compact'    => __( 'Compact List', 'unstoppable-voting-polls-elections' ),
			'tournament' => __( 'Tournament', 'unstoppable-voting-polls-elections' ),
		);
		/**
		 * Filter the registered frontend templates.
		 *
		 * Add-ons may register additional templates by returning
		 * {slug => label} entries and placing a matching PHP file in
		 * their own templates/ folder (loaded via `uvpe_template_path`).
		 *
		 * @since 2.1.0
		 *
		 * @param array $templates slug => human label.
		 */
		return apply_filters( 'uvpe_templates', $templates );
	}

	/**
	 * Allowed sort orders.
	 *
	 * @return array
	 */
	public static function get_sort_orders() {
		return array(
			'manual'     => __( 'Manual (drag to reorder)', 'unstoppable-voting-polls-elections' ),
			'alpha_asc'  => __( 'Alphabetical (A → Z)', 'unstoppable-voting-polls-elections' ),
			'alpha_desc' => __( 'Alphabetical (Z → A)', 'unstoppable-voting-polls-elections' ),
			'votes_desc' => __( 'Most votes first', 'unstoppable-voting-polls-elections' ),
			'votes_asc'  => __( 'Least votes first', 'unstoppable-voting-polls-elections' ),
			'random'     => __( 'Random', 'unstoppable-voting-polls-elections' ),
			'date_added' => __( 'Order added', 'unstoppable-voting-polls-elections' ),
		);
	}

	/**
	 * Allowed statuses.
	 *
	 * @return array
	 */
	public static function get_statuses() {
		return array(
			'draft'  => __( 'Draft', 'unstoppable-voting-polls-elections' ),
			'active' => __( 'Active', 'unstoppable-voting-polls-elections' ),
			'closed' => __( 'Closed', 'unstoppable-voting-polls-elections' ),
		);
	}

	/**
	 * Allowed result-display modes.
	 *
	 * @return array
	 */
	public static function get_results_display_modes() {
		return array(
			'before_vote' => __( 'Before voting', 'unstoppable-voting-polls-elections' ),
			'after_vote'  => __( 'After voting', 'unstoppable-voting-polls-elections' ),
			'when_closed' => __( 'Only when closed', 'unstoppable-voting-polls-elections' ),
			'never'       => __( 'Never (admin only)', 'unstoppable-voting-polls-elections' ),
		);
	}

	/**
	 * Allowed block-by modes.
	 *
	 * @return array
	 */
	public static function get_block_modes() {
		return array(
			'cookie' => __( 'Cookie (basic)', 'unstoppable-voting-polls-elections' ),
			'ip'     => __( 'IP address', 'unstoppable-voting-polls-elections' ),
			'both'   => __( 'Cookie + IP (strict)', 'unstoppable-voting-polls-elections' ),
			'none'   => __( 'No block (allow repeats — not recommended)', 'unstoppable-voting-polls-elections' ),
		);
	}

	/**
	 * Create a poll. Returns the new poll ID or WP_Error.
	 *
	 * @param array $data Sanitized poll data.
	 * @return int|WP_Error
	 */
	public static function create( $data ) {
		global $wpdb;
		$table = UVPE_Schema::table( 'polls' );

		$now = current_time( 'mysql' );

		$insert = array(
			'name'                 => $data['name'],
			'question'             => $data['question'],
			'description'          => isset( $data['description'] ) ? $data['description'] : '',
			'status'               => $data['status'],
			'poll_type'            => $data['poll_type'],
			'max_choices'          => $data['max_choices'],
			'template'             => $data['template'],
			'sort_order'           => $data['sort_order'],
			'results_display'      => $data['results_display'],
			'results_format'       => $data['results_format'],
			'allow_guests'         => $data['allow_guests'],
			'block_by'             => $data['block_by'],
			'vote_revision_window' => isset( $data['vote_revision_window'] ) ? absint( $data['vote_revision_window'] ) : 0,
			'start_date'           => $data['start_date'],
			'end_date'             => $data['end_date'],
			'author'               => get_current_user_id(),
			'added_date'           => $now,
			'modified_date'        => $now,
		);

		$formats = array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%d', '%s', '%s' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter  -- writing directly is intentional; caching not applicable here.
		$ok = $wpdb->insert( $table, $insert, $formats );
		if ( false === $ok ) {
			return new WP_Error( 'db_insert_failed', __( 'Could not save poll.', 'unstoppable-voting-polls-elections' ) );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update an existing poll.
	 *
	 * @param int   $poll_id Poll ID.
	 * @param array $data    Sanitized data.
	 * @return bool|WP_Error
	 */
	public static function update( $poll_id, $data ) {
		global $wpdb;
		$table = UVPE_Schema::table( 'polls' );

		$poll_id = absint( $poll_id );
		if ( $poll_id <= 0 ) {
			return new WP_Error( 'invalid_poll', __( 'Invalid poll ID.', 'unstoppable-voting-polls-elections' ) );
		}

		$update = array(
			'name'                 => $data['name'],
			'question'             => $data['question'],
			'description'          => isset( $data['description'] ) ? $data['description'] : '',
			'status'               => $data['status'],
			'poll_type'            => $data['poll_type'],
			'max_choices'          => $data['max_choices'],
			'template'             => $data['template'],
			'sort_order'           => $data['sort_order'],
			'results_display'      => $data['results_display'],
			'results_format'       => $data['results_format'],
			'allow_guests'         => $data['allow_guests'],
			'block_by'             => $data['block_by'],
			'vote_revision_window' => isset( $data['vote_revision_window'] ) ? absint( $data['vote_revision_window'] ) : 0,
			'start_date'           => $data['start_date'],
			'end_date'             => $data['end_date'],
			'modified_date'        => current_time( 'mysql' ),
		);

		$update_formats = array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter  -- direct write intentional.
		$result = $wpdb->update( $table, $update, array( 'id' => $poll_id ), $update_formats, array( '%d' ) );

		if ( false === $result ) {
			return new WP_Error( 'db_update_failed', __( 'Could not update poll.', 'unstoppable-voting-polls-elections' ) );
		}
		return true;
	}

	/**
	 * Delete a poll and all related answers and votes.
	 *
	 * @param int $poll_id Poll ID.
	 * @return bool
	 */
	public static function delete( $poll_id ) {
		global $wpdb;
		$poll_id = absint( $poll_id );
		if ( $poll_id <= 0 ) {
			return false;
		}

		$polls   = UVPE_Schema::table( 'polls' );
		$answers = UVPE_Schema::table( 'answers' );
		$votes   = UVPE_Schema::table( 'votes' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter  -- direct write intentional.
		$wpdb->delete( $votes, array( 'poll_id' => $poll_id ), array( '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->delete( $answers, array( 'poll_id' => $poll_id ), array( '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->delete( $polls, array( 'id' => $poll_id ), array( '%d' ) );

		return true;
	}

	/**
	 * Duplicate a poll: copies the poll row and its answers into a new draft.
	 *
	 * The duplicate is created with status "draft", vote counts reset to zero,
	 * and the name suffixed with " (Copy)". Votes are intentionally NOT copied.
	 *
	 * @param int $source_id Poll ID to duplicate.
	 * @return int|WP_Error New poll ID on success.
	 */
	public static function duplicate( $source_id ) {
		$source_id = absint( $source_id );
		if ( $source_id <= 0 ) {
			return new WP_Error( 'invalid_poll', __( 'Invalid poll ID.', 'unstoppable-voting-polls-elections' ) );
		}

		$source = self::get( $source_id );
		if ( ! $source ) {
			return new WP_Error( 'no_poll', __( 'Source poll not found.', 'unstoppable-voting-polls-elections' ) );
		}

		/**
		 * Filter the suffix appended to the duplicate's name.
		 *
		 * @since 2.1.0
		 *
		 * @param string $suffix Default " (Copy)".
		 * @param object $source Source poll row.
		 */
		$suffix = apply_filters( 'uvpe_duplicate_name_suffix', ' ' . __( '(Copy)', 'unstoppable-voting-polls-elections' ), $source );

		$data = array(
			'name'                 => mb_substr( $source->name . $suffix, 0, 255 ),
			'question'             => $source->question,
			'description'          => $source->description,
			'status'               => 'draft',
			'poll_type'            => $source->poll_type,
			'max_choices'          => (int) $source->max_choices,
			'template'             => $source->template,
			'sort_order'           => $source->sort_order,
			'results_display'      => $source->results_display,
			'results_format'       => $source->results_format,
			'allow_guests'         => (int) $source->allow_guests,
			'block_by'             => $source->block_by,
			'vote_revision_window' => isset( $source->vote_revision_window ) ? (int) $source->vote_revision_window : 0,
			'start_date'           => null,
			'end_date'             => null,
		);

		/**
		 * Filter the data used to create a duplicated poll before insert.
		 *
		 * Pro add-ons may use this to copy premium per-poll fields.
		 *
		 * @since 2.1.0
		 *
		 * @param array  $data   Poll-insert data for the new copy.
		 * @param object $source Source poll row.
		 */
		$data = apply_filters( 'uvpe_duplicate_poll_data', $data, $source );

		$new_id = self::create( $data );
		if ( is_wp_error( $new_id ) ) {
			return $new_id;
		}

		// Copy answers (text, subtitle, image, sort position). Vote counts stay zero.
		$answers = self::get_answers( $source_id, 'manual' );
		if ( $answers ) {
			$copy_rows = array();
			foreach ( $answers as $a ) {
				$copy_rows[] = array(
					'id'            => 0, // mark as "new" row for save_answers
					'text'          => $a->answer_text,
					'subtitle'      => isset( $a->subtitle ) ? $a->subtitle : '',
					'image_url'     => isset( $a->image_url ) ? $a->image_url : '',
					'sort_position' => (int) $a->sort_position,
				);
			}
			self::save_answers( $new_id, $copy_rows );
		}

		/**
		 * Fires after a poll has been duplicated.
		 *
		 * @since 2.1.0
		 *
		 * @param int    $new_id    New poll ID.
		 * @param int    $source_id Original poll ID.
		 * @param object $source    Source poll row.
		 */
		do_action( 'uvpe_poll_duplicated', $new_id, $source_id, $source );

		return (int) $new_id;
	}

	/**
	 * Get a single poll by ID.
	 *
	 * @param int $poll_id Poll ID.
	 * @return object|null
	 */
	public static function get( $poll_id ) {
		global $wpdb;
		$poll_id = absint( $poll_id );
		if ( $poll_id <= 0 ) {
			return null;
		}
		$table = UVPE_Schema::table( 'polls' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter  -- table identifier interpolated; values prepared.
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $poll_id ) );
	}

	/**
	 * Get a list of polls with optional filters.
	 *
	 * @param array $args { status, search, limit, offset, orderby, order }.
	 * @return array
	 */
	public static function get_all( $args = array() ) {
		global $wpdb;
		$table = UVPE_Schema::table( 'polls' );

		$defaults = array(
			'status'  => '',
			'search'  => '',
			'limit'   => 50,
			'offset'  => 0,
			'orderby' => 'added_date',
			'order'   => 'DESC',
		);
		$args = wp_parse_args( $args, $defaults );

		$where  = ' WHERE 1=1 ';
		$params = array();

		if ( ! empty( $args['status'] ) && array_key_exists( $args['status'], self::get_statuses() ) ) {
			$where   .= ' AND status = %s ';
			$params[] = $args['status'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where   .= ' AND (name LIKE %s OR question LIKE %s) ';
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		// Whitelist orderby and order to prevent SQL injection.
		$allowed_orderby = array( 'id', 'name', 'status', 'total_votes', 'added_date', 'modified_date' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'added_date';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$limit  = absint( $args['limit'] );
		$offset = absint( $args['offset'] );

		$sql = "SELECT * FROM {$table}" . $where . " ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$params[] = $limit;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table/orderby/order whitelisted; values prepared.
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Count polls (used for pagination + dashboard stats).
	 *
	 * @param string $status Optional status filter.
	 * @return int
	 */
	public static function count( $status = '' ) {
		global $wpdb;
		$table = UVPE_Schema::table( 'polls' );

		if ( $status && array_key_exists( $status, self::get_statuses() ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status ) );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	/* ------------------------------------------------------------------
	 * Answers
	 * ------------------------------------------------------------------ */

	/**
	 * Replace all answers for a poll.
	 *
	 * Used when saving the poll edit form. Existing answers (with vote counts)
	 * are matched by ID and preserved; new answers are inserted; missing IDs
	 * are removed (along with their votes).
	 *
	 * @param int   $poll_id  Poll ID.
	 * @param array $answers  Array of answer arrays.
	 * @return bool
	 */
	public static function save_answers( $poll_id, $answers ) {
		global $wpdb;
		$poll_id = absint( $poll_id );
		if ( $poll_id <= 0 ) {
			return false;
		}

		$table        = UVPE_Schema::table( 'answers' );
		$votes_table  = UVPE_Schema::table( 'votes' );
		$existing_ids = self::get_answer_ids( $poll_id );
		$kept_ids     = array();
		$now          = current_time( 'mysql' );

		foreach ( $answers as $position => $answer ) {
			$answer_text = isset( $answer['text'] ) ? sanitize_text_field( $answer['text'] ) : '';
			if ( '' === $answer_text ) {
				continue;
			}
			$subtitle  = isset( $answer['subtitle'] ) ? sanitize_text_field( $answer['subtitle'] ) : '';
			$image_url = isset( $answer['image_url'] ) ? esc_url_raw( $answer['image_url'] ) : '';
			$answer_id = isset( $answer['id'] ) ? absint( $answer['id'] ) : 0;

			if ( $answer_id > 0 && in_array( $answer_id, $existing_ids, true ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$wpdb->update(
					$table,
					array(
						'answer_text'   => $answer_text,
						'subtitle'      => $subtitle,
						'image_url'     => $image_url,
						'sort_position' => $position,
					),
					array( 'id' => $answer_id, 'poll_id' => $poll_id ),
					array( '%s', '%s', '%s', '%d' ),
					array( '%d', '%d' )
				);
				$kept_ids[] = $answer_id;
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$wpdb->insert(
					$table,
					array(
						'poll_id'       => $poll_id,
						'answer_text'   => $answer_text,
						'subtitle'      => $subtitle,
						'image_url'     => $image_url,
						'sort_position' => $position,
						'vote_count'    => 0,
						'added_date'    => $now,
					),
					array( '%d', '%s', '%s', '%s', '%d', '%d', '%s' )
				);
			}
		}

		// Delete answers (and their votes) that were removed from the form.
		$to_delete = array_diff( $existing_ids, $kept_ids );
		foreach ( $to_delete as $remove_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->delete( $votes_table, array( 'answer_id' => $remove_id ), array( '%d' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->delete( $table, array( 'id' => $remove_id ), array( '%d' ) );
		}

		// Recalculate total vote count on the poll.
		self::recalc_total_votes( $poll_id );

		return true;
	}

	/**
	 * Get answers for a poll, ordered by the poll's sort_order setting.
	 *
	 * @param int    $poll_id    Poll ID.
	 * @param string $sort_order Sort key (see get_sort_orders()).
	 * @return array
	 */
	public static function get_answers( $poll_id, $sort_order = 'manual' ) {
		global $wpdb;
		$poll_id = absint( $poll_id );
		if ( $poll_id <= 0 ) {
			return array();
		}
		$table = UVPE_Schema::table( 'answers' );

		switch ( $sort_order ) {
			case 'alpha_asc':
				$order_sql = 'ORDER BY answer_text ASC';
				break;
			case 'alpha_desc':
				$order_sql = 'ORDER BY answer_text DESC';
				break;
			case 'votes_desc':
				$order_sql = 'ORDER BY vote_count DESC, sort_position ASC';
				break;
			case 'votes_asc':
				$order_sql = 'ORDER BY vote_count ASC, sort_position ASC';
				break;
			case 'random':
				$order_sql = 'ORDER BY RAND()';
				break;
			case 'date_added':
				$order_sql = 'ORDER BY added_date ASC, id ASC';
				break;
			case 'manual':
			default:
				$order_sql = 'ORDER BY sort_position ASC, id ASC';
				break;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter  -- order_sql is whitelisted above.
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE poll_id = %d {$order_sql}", $poll_id ) );
	}

	/**
	 * Get just the IDs of all answers for a poll.
	 *
	 * @param int $poll_id Poll ID.
	 * @return int[]
	 */
	public static function get_answer_ids( $poll_id ) {
		global $wpdb;
		$table = UVPE_Schema::table( 'answers' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE poll_id = %d", $poll_id ) );
		return array_map( 'intval', $ids );
	}

	/**
	 * Recalculate poll.total_votes from the votes table.
	 *
	 * @param int $poll_id Poll ID.
	 */
	public static function recalc_total_votes( $poll_id ) {
		global $wpdb;
		$poll_id = absint( $poll_id );
		if ( $poll_id <= 0 ) {
			return;
		}

		$polls = UVPE_Schema::table( 'polls' );
		$votes = UVPE_Schema::table( 'votes' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$votes} WHERE poll_id = %d", $poll_id ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->update( $polls, array( 'total_votes' => $total ), array( 'id' => $poll_id ), array( '%d' ), array( '%d' ) );
	}

	/**
	 * Recalculate vote_count on every answer for a poll.
	 *
	 * @param int $poll_id Poll ID.
	 */
	public static function recalc_answer_votes( $poll_id ) {
		global $wpdb;
		$poll_id = absint( $poll_id );
		if ( $poll_id <= 0 ) {
			return;
		}

		$answers = UVPE_Schema::table( 'answers' );
		$votes   = UVPE_Schema::table( 'votes' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table identifiers built from $wpdb->prefix; values bound via prepare().
		$wpdb->query( $wpdb->prepare( "UPDATE {$answers} a SET vote_count = ( SELECT COUNT(*) FROM {$votes} v WHERE v.answer_id = a.id ) WHERE a.poll_id = %d", $poll_id ) );
	}

	/**
	 * Check whether the poll is currently accepting votes.
	 *
	 * @param object $poll Poll row.
	 * @return bool
	 */
	public static function is_open( $poll ) {
		if ( ! $poll || 'active' !== $poll->status ) {
			return false;
		}
		$now        = current_time( 'mysql' );
		$start_date = isset( $poll->start_date ) ? (string) $poll->start_date : '';
		$end_date   = isset( $poll->end_date )   ? (string) $poll->end_date   : '';

		// Treat MySQL zero-dates ("0000-00-00 00:00:00") as "unset" — some older
		// rows may have been stored that way before the datetime sanitizer was
		// taught to return NULL for empty values.
		if ( '0000-00-00 00:00:00' === $start_date ) {
			$start_date = '';
		}
		if ( '0000-00-00 00:00:00' === $end_date ) {
			$end_date = '';
		}

		if ( '' !== $start_date && $start_date > $now ) {
			return false;
		}
		if ( '' !== $end_date && $end_date < $now ) {
			return false;
		}
		return true;
	}
}
