<?php
/**
 * Template: Classic Bars
 *
 * Variables available:
 *   $poll          stdClass poll row
 *   $answers       array of answer rows
 *   $is_open       bool
 *   $already_voted bool
 *   $show_results  bool
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local view/template variables, not globals.
?>
<div class="uvpe-poll uvpe-poll--classic" data-poll-id="<?php echo esc_attr( $poll->id ); ?>">
	<div class="uvpe-poll__header">
		<h3 class="uvpe-poll__question"><?php echo esc_html( $poll->question ); ?></h3>
		<?php if ( ! empty( $poll->description ) ) : ?>
			<p class="uvpe-poll__description"><?php echo esc_html( $poll->description ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( $show_results ) : ?>
		<ul class="uvpe-results">
			<?php foreach ( $answers as $answer ) :
				$percent = UVPE_Templates::percentage( $answer, $poll->total_votes );
				?>
				<li class="uvpe-results__row">
					<div class="uvpe-results__label-row">
						<span class="uvpe-results__name"><?php echo esc_html( $answer->answer_text ); ?></span>
						<span class="uvpe-results__value"><?php echo UVPE_Templates::format_result( $poll, $answer, $poll->total_votes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- format_result escapes its own content. ?></span>
					</div>
					<div class="uvpe-results__bar">
						<div class="uvpe-results__bar-fill" style="width: <?php echo esc_attr( $percent ); ?>%;"></div>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="uvpe-poll__total">
			<?php printf(
				/* translators: %s: total number of votes. */
				esc_html__( 'Total votes: %s', 'unstoppable-voting-polls-elections' ),
				esc_html( number_format_i18n( $poll->total_votes ) )
			); ?>
		</p>
	<?php elseif ( $is_open ) : ?>
		<form class="uvpe-form" data-poll-id="<?php echo esc_attr( $poll->id ); ?>">
			<?php wp_nonce_field( 'uvpe_vote_' . $poll->id, 'uvpe_nonce' ); ?>
			<input type="hidden" name="poll_id" value="<?php echo esc_attr( $poll->id ); ?>">
			<input type="hidden" name="poll_type" value="<?php echo esc_attr( $poll->poll_type ); ?>">

			<ul class="uvpe-options">
				<?php foreach ( $answers as $answer ) :
					$input_type = 'single' === $poll->poll_type ? 'radio' : 'checkbox';
					$input_id   = 'uvpe-answer-' . $poll->id . '-' . $answer->id;
					?>
					<li class="uvpe-options__item">
						<label for="<?php echo esc_attr( $input_id ); ?>" class="uvpe-options__label">
							<input type="<?php echo esc_attr( $input_type ); ?>"
								id="<?php echo esc_attr( $input_id ); ?>"
								name="answer_ids[]"
								value="<?php echo esc_attr( $answer->id ); ?>"
								class="uvpe-options__input">
							<span class="uvpe-options__text"><?php echo esc_html( $answer->answer_text ); ?></span>
							<?php if ( ! empty( $answer->subtitle ) ) : ?>
								<span class="uvpe-options__sub"><?php echo esc_html( $answer->subtitle ); ?></span>
							<?php endif; ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php if ( 'multiple' === $poll->poll_type && (int) $poll->max_choices > 1 ) : ?>
				<p class="uvpe-form__hint">
					<?php printf(
						/* translators: %d: max number of allowed selections. */
						esc_html__( 'You may select up to %d.', 'unstoppable-voting-polls-elections' ),
						(int) $poll->max_choices
					); ?>
				</p>
			<?php endif; ?>

			<div class="uvpe-form__footer">
				<button type="submit" class="uvpe-form__submit"><?php esc_html_e( 'Submit Vote', 'unstoppable-voting-polls-elections' ); ?></button>
				<span class="uvpe-form__message" role="status" aria-live="polite"></span>
			</div>
		</form>
	<?php else : ?>
		<p class="uvpe-poll__closed-notice">
			<?php esc_html_e( 'This poll is closed.', 'unstoppable-voting-polls-elections' ); ?>
		</p>
	<?php endif; ?>
</div>
