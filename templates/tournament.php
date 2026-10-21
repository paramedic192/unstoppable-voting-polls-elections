<?php
/**
 * Template: Tournament
 *
 * Pairs options as head-to-head matchups. Best when used with multi-choice
 * polls limited to picking 2-N options (e.g. "Pick the 4 teams to send").
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local view/template variables, not globals.

$is_multi = 'multiple' === $poll->poll_type;
?>
<div class="uvpe-poll uvpe-poll--tournament" data-poll-id="<?php echo esc_attr( $poll->id ); ?>">
	<div class="uvpe-poll__header uvpe-poll__header--tournament">
		<span class="uvpe-tournament__badge"><?php esc_html_e( 'Tournament', 'unstoppable-voting-polls-elections' ); ?></span>
		<h3 class="uvpe-poll__question"><?php echo esc_html( $poll->question ); ?></h3>
		<?php if ( ! empty( $poll->description ) ) : ?>
			<p class="uvpe-poll__description"><?php echo esc_html( $poll->description ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( $show_results ) : ?>
		<div class="uvpe-tournament__results">
			<?php
			// Sort by votes for results.
			$ranked = $answers;
			usort( $ranked, function ( $a, $b ) {
				return (int) $b->vote_count - (int) $a->vote_count;
			} );

			$max_vote = 0;
			foreach ( $ranked as $a ) {
				if ( (int) $a->vote_count > $max_vote ) {
					$max_vote = (int) $a->vote_count;
				}
			}

			foreach ( $ranked as $i => $answer ) :
				$is_winner = ( 0 === $i && $max_vote > 0 );
				$percent   = UVPE_Templates::percentage( $answer, $poll->total_votes );
				?>
				<div class="uvpe-tournament__row<?php echo $is_winner ? ' is-winner' : ''; ?>">
					<span class="uvpe-tournament__seed">#<?php echo esc_html( $i + 1 ); ?></span>
					<?php if ( ! empty( $answer->image_url ) ) : ?>
						<span class="uvpe-tournament__logo" style="background-image: url('<?php echo esc_url( $answer->image_url ); ?>');" aria-hidden="true"></span>
					<?php endif; ?>
					<span class="uvpe-tournament__name"><?php echo esc_html( $answer->answer_text ); ?></span>
					<span class="uvpe-tournament__bar" aria-hidden="true">
						<span class="uvpe-tournament__fill" style="width: <?php echo esc_attr( $percent ); ?>%;"></span>
					</span>
					<span class="uvpe-tournament__value">
						<?php echo UVPE_Templates::format_result( $poll, $answer, $poll->total_votes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="uvpe-poll__total">
			<?php printf(
				/* translators: %s: total votes. */
				esc_html__( 'Total votes: %s', 'unstoppable-voting-polls-elections' ),
				esc_html( number_format_i18n( $poll->total_votes ) )
			); ?>
		</p>
	<?php elseif ( $is_open ) : ?>
		<form class="uvpe-form" data-poll-id="<?php echo esc_attr( $poll->id ); ?>">
			<?php wp_nonce_field( 'uvpe_vote_' . $poll->id, 'uvpe_nonce' ); ?>
			<input type="hidden" name="poll_id" value="<?php echo esc_attr( $poll->id ); ?>">
			<input type="hidden" name="poll_type" value="<?php echo esc_attr( $poll->poll_type ); ?>">

			<div class="uvpe-tournament__bracket">
				<?php
				$pairs = array_chunk( $answers, 2 );
				foreach ( $pairs as $pair_index => $pair ) : ?>
					<div class="uvpe-tournament__matchup">
						<?php foreach ( $pair as $side => $answer ) :
							$input_type = $is_multi ? 'checkbox' : 'radio';
							$input_id   = 'uvpe-answer-' . $poll->id . '-' . $answer->id;
							?>
							<label for="<?php echo esc_attr( $input_id ); ?>" class="uvpe-tournament__side">
								<input type="<?php echo esc_attr( $input_type ); ?>"
									id="<?php echo esc_attr( $input_id ); ?>"
									name="answer_ids[]"
									value="<?php echo esc_attr( $answer->id ); ?>"
									class="uvpe-tournament__input">
								<?php if ( ! empty( $answer->image_url ) ) : ?>
									<span class="uvpe-tournament__side-logo" style="background-image: url('<?php echo esc_url( $answer->image_url ); ?>');" aria-hidden="true"></span>
								<?php else : ?>
									<span class="uvpe-tournament__side-logo uvpe-tournament__side-logo--placeholder" aria-hidden="true">
										<?php echo esc_html( strtoupper( substr( $answer->answer_text, 0, 1 ) ) ); ?>
									</span>
								<?php endif; ?>
								<span class="uvpe-tournament__side-text">
									<span class="uvpe-tournament__side-name"><?php echo esc_html( $answer->answer_text ); ?></span>
									<?php if ( ! empty( $answer->subtitle ) ) : ?>
										<span class="uvpe-tournament__side-sub"><?php echo esc_html( $answer->subtitle ); ?></span>
									<?php endif; ?>
								</span>
							</label>
							<?php if ( 0 === $side && isset( $pair[1] ) ) : ?>
								<span class="uvpe-tournament__vs" aria-hidden="true">vs</span>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $is_multi && (int) $poll->max_choices > 1 ) : ?>
				<p class="uvpe-form__hint">
					<?php printf(
						/* translators: %d: max selections. */
						esc_html__( 'Select up to %d.', 'unstoppable-voting-polls-elections' ),
						(int) $poll->max_choices
					); ?>
				</p>
			<?php endif; ?>

			<div class="uvpe-form__footer">
				<button type="submit" class="uvpe-form__submit"><?php esc_html_e( 'Submit Picks', 'unstoppable-voting-polls-elections' ); ?></button>
				<span class="uvpe-form__message" role="status" aria-live="polite"></span>
			</div>
		</form>
	<?php else : ?>
		<p class="uvpe-poll__closed-notice"><?php esc_html_e( 'Voting is closed.', 'unstoppable-voting-polls-elections' ); ?></p>
	<?php endif; ?>
</div>
