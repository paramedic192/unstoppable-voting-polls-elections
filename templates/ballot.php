<?php
/**
 * Template: Official Ballot
 *
 * Designed for officer elections and other formal votes. Uses a paper-ballot
 * visual treatment with numbered options and a clear, official look.
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local view/template variables, not globals.
?>
<div class="uvpe-poll uvpe-poll--ballot" data-poll-id="<?php echo esc_attr( $poll->id ); ?>">
	<div class="uvpe-ballot">
		<div class="uvpe-ballot__header">
			<span class="uvpe-ballot__badge"><?php esc_html_e( 'Official Ballot', 'unstoppable-voting-polls-elections' ); ?></span>
			<h3 class="uvpe-ballot__question"><?php echo esc_html( $poll->question ); ?></h3>
			<?php if ( ! empty( $poll->description ) ) : ?>
				<p class="uvpe-ballot__description"><?php echo esc_html( $poll->description ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $show_results ) : ?>
			<div class="uvpe-ballot__results">
				<h4 class="uvpe-ballot__results-title"><?php esc_html_e( 'Results', 'unstoppable-voting-polls-elections' ); ?></h4>
				<ol class="uvpe-ballot__results-list">
					<?php
					// Sort by votes for a results view, regardless of poll sort.
					$ranked = $answers;
					usort( $ranked, function ( $a, $b ) {
						return (int) $b->vote_count - (int) $a->vote_count;
					} );
					foreach ( $ranked as $i => $answer ) :
						$is_leader = ( 0 === $i && (int) $answer->vote_count > 0 );
						?>
						<li class="uvpe-ballot__results-row<?php echo $is_leader ? ' is-leader' : ''; ?>">
							<span class="uvpe-ballot__results-rank"><?php echo esc_html( $i + 1 ); ?></span>
							<span class="uvpe-ballot__results-name"><?php echo esc_html( $answer->answer_text ); ?></span>
							<span class="uvpe-ballot__results-value">
								<?php echo UVPE_Templates::format_result( $poll, $answer, $poll->total_votes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- format_result escapes its content. ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ol>
				<p class="uvpe-poll__total">
					<?php printf(
						/* translators: %s: total number of votes. */
						esc_html__( 'Total votes cast: %s', 'unstoppable-voting-polls-elections' ),
						esc_html( number_format_i18n( $poll->total_votes ) )
					); ?>
				</p>
			</div>
		<?php elseif ( $is_open ) : ?>
			<form class="uvpe-form uvpe-ballot__form" data-poll-id="<?php echo esc_attr( $poll->id ); ?>">
				<?php wp_nonce_field( 'uvpe_vote_' . $poll->id, 'uvpe_nonce' ); ?>
				<input type="hidden" name="poll_id" value="<?php echo esc_attr( $poll->id ); ?>">
				<input type="hidden" name="poll_type" value="<?php echo esc_attr( $poll->poll_type ); ?>">

				<p class="uvpe-ballot__instruction">
					<?php if ( 'single' === $poll->poll_type ) : ?>
						<?php esc_html_e( 'Select one option:', 'unstoppable-voting-polls-elections' ); ?>
					<?php else : ?>
						<?php printf(
							/* translators: %d: max selections. */
							esc_html__( 'Select up to %d:', 'unstoppable-voting-polls-elections' ),
							(int) $poll->max_choices
						); ?>
					<?php endif; ?>
				</p>

				<ol class="uvpe-ballot__options">
					<?php foreach ( $answers as $i => $answer ) :
						$input_type = 'single' === $poll->poll_type ? 'radio' : 'checkbox';
						$input_id   = 'uvpe-answer-' . $poll->id . '-' . $answer->id;
						?>
						<li class="uvpe-ballot__option">
							<label for="<?php echo esc_attr( $input_id ); ?>" class="uvpe-ballot__option-label">
								<span class="uvpe-ballot__option-number"><?php echo esc_html( $i + 1 ); ?></span>
								<input type="<?php echo esc_attr( $input_type ); ?>"
									id="<?php echo esc_attr( $input_id ); ?>"
									name="answer_ids[]"
									value="<?php echo esc_attr( $answer->id ); ?>"
									class="uvpe-ballot__option-input">
								<span class="uvpe-ballot__option-text">
									<span class="uvpe-ballot__option-name"><?php echo esc_html( $answer->answer_text ); ?></span>
									<?php if ( ! empty( $answer->subtitle ) ) : ?>
										<span class="uvpe-ballot__option-sub"><?php echo esc_html( $answer->subtitle ); ?></span>
									<?php endif; ?>
								</span>
							</label>
						</li>
					<?php endforeach; ?>
				</ol>

				<div class="uvpe-form__footer">
					<button type="submit" class="uvpe-form__submit"><?php esc_html_e( 'Cast My Vote', 'unstoppable-voting-polls-elections' ); ?></button>
					<span class="uvpe-form__message" role="status" aria-live="polite"></span>
				</div>
			</form>
		<?php else : ?>
			<p class="uvpe-poll__closed-notice">
				<?php esc_html_e( 'Voting is closed.', 'unstoppable-voting-polls-elections' ); ?>
			</p>
		<?php endif; ?>
	</div>
</div>
