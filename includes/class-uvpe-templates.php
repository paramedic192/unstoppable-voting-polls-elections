<?php
/**
 * Template Renderer.
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders polls using one of the bundled templates.
 */
class UVPE_Templates {

	/**
	 * Render a poll for the front end.
	 *
	 * @param object $poll Poll row.
	 * @return string HTML output.
	 */
	public static function render( $poll ) {
		if ( ! $poll ) {
			return '';
		}

		$templates = UVPE_Poll::get_templates();
		$template  = array_key_exists( $poll->template, $templates ) ? $poll->template : 'classic';

		$answers       = UVPE_Poll::get_answers( $poll->id, $poll->sort_order );
		$is_open       = UVPE_Poll::is_open( $poll );
		$already_voted = UVPE_Vote::has_already_voted( $poll );
		$show_results  = self::should_show_results( $poll, $already_voted );

		$template_file = UVPE_PLUGIN_DIR . 'templates/' . $template . '.php';
		if ( ! file_exists( $template_file ) ) {
			$template_file = UVPE_PLUGIN_DIR . 'templates/classic.php';
		}

		// Final check: make sure the resolved path is inside our plugin dir.
		$real_template = realpath( $template_file );
		$real_dir      = realpath( UVPE_PLUGIN_DIR . 'templates/' );
		if ( false === $real_template || false === $real_dir || 0 !== strpos( $real_template, $real_dir ) ) {
			return '';
		}

		ob_start();
		include $real_template;
		return ob_get_clean();
	}

	/**
	 * Decide whether to render results vs the voting form.
	 *
	 * @param object $poll          Poll row.
	 * @param bool   $already_voted Whether this visitor has voted.
	 * @return bool
	 */
	public static function should_show_results( $poll, $already_voted ) {
		switch ( $poll->results_display ) {
			case 'before_vote':
				return true;
			case 'never':
				return false;
			case 'when_closed':
				return ! UVPE_Poll::is_open( $poll );
			case 'after_vote':
			default:
				return $already_voted || ! UVPE_Poll::is_open( $poll );
		}
	}

	/**
	 * Calculate the display percentage for an answer.
	 *
	 * @param object $answer       Answer row.
	 * @param int    $total_votes  Total votes on the poll.
	 * @return float
	 */
	public static function percentage( $answer, $total_votes ) {
		$total_votes = (int) $total_votes;
		if ( $total_votes <= 0 ) {
			return 0.0;
		}
		return round( ( (int) $answer->vote_count / $total_votes ) * 100, 1 );
	}

	/**
	 * Format a vote count according to the poll's results_format.
	 *
	 * @param object $poll        Poll row.
	 * @param object $answer      Answer row.
	 * @param int    $total_votes Total votes on the poll.
	 * @return string Already-escaped HTML.
	 */
	public static function format_result( $poll, $answer, $total_votes ) {
		$count   = (int) $answer->vote_count;
		$percent = self::percentage( $answer, $total_votes );

		switch ( $poll->results_format ) {
			case 'number':
				return esc_html( number_format_i18n( $count ) ) . ' <span class="uvpe-results__label">' . esc_html__( 'votes', 'unstoppable-voting-polls-elections' ) . '</span>';
			case 'percent':
				return esc_html( number_format_i18n( $percent, 1 ) ) . '%';
			case 'both':
			default:
				return esc_html( number_format_i18n( $percent, 1 ) ) . '% <span class="uvpe-results__sub">(' . esc_html( number_format_i18n( $count ) ) . ')</span>';
		}
	}
}
