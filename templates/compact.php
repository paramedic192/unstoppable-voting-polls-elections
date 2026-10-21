<?php
/**
 * Template: Compact List
 *
 * Minimal, low-chrome treatment. Good for sidebars or quick polls.
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local view/template variables, not globals.
?>
<div class="uvpe-poll uvpe-poll--compact" data-poll-id="<?php echo esc_attr( $poll->id ); ?>">
	<h3 class="uvpe-poll__question uvpe-poll__question--compact"><?php echo esc_html( $poll->question ); ?></h3>

	<?php if ( $show_results ) : ?>
		<ul class="uvpe-compact__results">
			<?php foreach ( $answers as $answer ) :
				$percent = UVPE_Templates::percentage( $answer, $poll->total_votes );
				?>
				<li class="uvpe-compact__result-row">
					<span class="uvpe-compact__result-name"><?php echo esc_html( $answer->answer_text ); ?></span>
					<span class="uvpe-compact__result-bar" aria-hidden="true">
						<span class="uvpe-compact__result-fill" style="width: <?php echo esc_attr( $percent ); ?>%;"></span>
					</span>
					<span class="uvpe-compact__result-value">
						<?php echo UVPE_Templates::format_result( $poll, $answer, $poll->total_votes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="uvpe-poll__total uvpe-poll__total--compact">
			<?php echo esc_html( number_format_i18n( $poll->total_votes ) ); ?>
			<?php esc_html_e( 'votes', 'unstoppable-voting-polls-elections' ); ?>
		</p>
	<?php elseif ( $is_open ) : ?>
		<form class="uvpe-form uvpe-form--compact" data-poll-id="<?php echo esc_attr( $poll->id ); ?>">
			<?php wp_nonce_field( 'uvpe_vote_' . $poll->id, 'uvpe_nonce' ); ?>
			<input type="hidden" name="poll_id" value="<?php echo esc_attr( $poll->id ); ?>">
			<input type="hidden" name="poll_type" value="<?php echo esc_attr( $poll->poll_type ); ?>">

			<ul class="uvpe-compact__options">
				<?php foreach ( $answers as $answer ) :
					$input_type = 'single' === $poll->poll_type ? 'radio' : 'checkbox';
					$input_id   = 'uvpe-answer-' . $poll->id . '-' . $answer->id;
					?>
					<li>
						<label for="<?php echo esc_attr( $input_id ); ?>" class="uvpe-compact__label">
							<input type="<?php echo esc_attr( $input_type ); ?>"
								id="<?php echo esc_attr( $input_id ); ?>"
								name="answer_ids[]"
								value="<?php echo esc_attr( $answer->id ); ?>">
							<?php echo esc_html( $answer->answer_text ); ?>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="uvpe-form__footer">
				<button type="submit" class="uvpe-form__submit uvpe-form__submit--small"><?php esc_html_e( 'Vote', 'unstoppable-voting-polls-elections' ); ?></button>
				<span class="uvpe-form__message" role="status" aria-live="polite"></span>
			</div>
		</form>
	<?php else : ?>
		<p class="uvpe-poll__closed-notice"><?php esc_html_e( 'Closed.', 'unstoppable-voting-polls-elections' ); ?></p>
	<?php endif; ?>
</div>
