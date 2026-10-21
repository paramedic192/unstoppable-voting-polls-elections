<?php
/**
 * Template: Candidate Cards
 *
 * Photo + name + subtitle on each option card. Ideal for officer elections
 * with candidate headshots or tournament selection with team logos.
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local view/template variables, not globals.

$is_multi = 'multiple' === $poll->poll_type;
?>
<div class="uvpe-poll uvpe-poll--cards" data-poll-id="<?php echo esc_attr( $poll->id ); ?>">
	<div class="uvpe-poll__header">
		<h3 class="uvpe-poll__question"><?php echo esc_html( $poll->question ); ?></h3>
		<?php if ( ! empty( $poll->description ) ) : ?>
			<p class="uvpe-poll__description"><?php echo esc_html( $poll->description ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( $show_results ) : ?>
		<div class="uvpe-cards-grid uvpe-cards-grid--results">
			<?php foreach ( $answers as $answer ) :
				$percent = UVPE_Templates::percentage( $answer, $poll->total_votes );
				?>
				<div class="uvpe-card uvpe-card--result">
					<?php if ( ! empty( $answer->image_url ) ) : ?>
						<div class="uvpe-card__image" style="background-image: url('<?php echo esc_url( $answer->image_url ); ?>');" role="img" aria-label="<?php echo esc_attr( $answer->answer_text ); ?>"></div>
					<?php else : ?>
						<div class="uvpe-card__image uvpe-card__image--placeholder" aria-hidden="true">
							<span><?php echo esc_html( strtoupper( substr( $answer->answer_text, 0, 1 ) ) ); ?></span>
						</div>
					<?php endif; ?>
					<div class="uvpe-card__body">
						<h4 class="uvpe-card__name"><?php echo esc_html( $answer->answer_text ); ?></h4>
						<?php if ( ! empty( $answer->subtitle ) ) : ?>
							<p class="uvpe-card__sub"><?php echo esc_html( $answer->subtitle ); ?></p>
						<?php endif; ?>
						<div class="uvpe-card__bar">
							<div class="uvpe-card__bar-fill" style="width: <?php echo esc_attr( $percent ); ?>%;"></div>
						</div>
						<p class="uvpe-card__result">
							<?php echo UVPE_Templates::format_result( $poll, $answer, $poll->total_votes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</p>
					</div>
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

			<div class="uvpe-cards-grid">
				<?php foreach ( $answers as $answer ) :
					$input_type = $is_multi ? 'checkbox' : 'radio';
					$input_id   = 'uvpe-answer-' . $poll->id . '-' . $answer->id;
					?>
					<label for="<?php echo esc_attr( $input_id ); ?>" class="uvpe-card uvpe-card--selectable">
						<input type="<?php echo esc_attr( $input_type ); ?>"
							id="<?php echo esc_attr( $input_id ); ?>"
							name="answer_ids[]"
							value="<?php echo esc_attr( $answer->id ); ?>"
							class="uvpe-card__input">
						<?php if ( ! empty( $answer->image_url ) ) : ?>
							<div class="uvpe-card__image" style="background-image: url('<?php echo esc_url( $answer->image_url ); ?>');" role="img" aria-label="<?php echo esc_attr( $answer->answer_text ); ?>"></div>
						<?php else : ?>
							<div class="uvpe-card__image uvpe-card__image--placeholder" aria-hidden="true">
								<span><?php echo esc_html( strtoupper( substr( $answer->answer_text, 0, 1 ) ) ); ?></span>
							</div>
						<?php endif; ?>
						<div class="uvpe-card__body">
							<h4 class="uvpe-card__name"><?php echo esc_html( $answer->answer_text ); ?></h4>
							<?php if ( ! empty( $answer->subtitle ) ) : ?>
								<p class="uvpe-card__sub"><?php echo esc_html( $answer->subtitle ); ?></p>
							<?php endif; ?>
						</div>
						<span class="uvpe-card__check" aria-hidden="true"></span>
					</label>
				<?php endforeach; ?>
			</div>

			<?php if ( $is_multi && (int) $poll->max_choices > 1 ) : ?>
				<p class="uvpe-form__hint">
					<?php printf(
						/* translators: %d: max selections. */
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
