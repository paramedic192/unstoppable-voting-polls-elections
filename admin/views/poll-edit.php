<?php
/**
 * Admin View: Add / Edit Poll.
 *
 * Variables available:
 *   $poll, $answers, $flash
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local view/template variables, not globals.

/**
 * Render a single answer row. Used for the server-side loop and JS template.
 *
 * @param int|string  $index  Row index (or "__INDEX__" placeholder for JS template).
 * @param object|null $answer Optional existing answer row.
 */
if ( ! function_exists( 'uvpe_admin_print_answer_row' ) ) {
	function uvpe_admin_print_answer_row( $index, $answer = null ) {
		$id        = ( $answer && isset( $answer->id ) ) ? (int) $answer->id : 0;
		$text      = ( $answer && isset( $answer->answer_text ) ) ? $answer->answer_text : '';
		$subtitle  = ( $answer && isset( $answer->subtitle ) ) ? $answer->subtitle : '';
		$image_url = ( $answer && isset( $answer->image_url ) ) ? $answer->image_url : '';
		// $index may be a literal "__INDEX__" placeholder string for JS templating.
		// Inline escape on each output for Plugin Check compatibility.
		?>
		<div class="uvpe-answer-row" data-index="<?php echo esc_attr( $index ); ?>">
			<span class="uvpe-answer-row__handle" aria-hidden="true">⋮⋮</span>
			<input type="hidden" name="answers[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $id ); ?>">
			<div class="uvpe-answer-row__fields">
				<input type="text"
					name="answers[<?php echo esc_attr( $index ); ?>][text]"
					value="<?php echo esc_attr( $text ); ?>"
					placeholder="<?php esc_attr_e( 'Option name (e.g. candidate, choice, item)', 'unstoppable-voting-polls-elections' ); ?>"
					class="regular-text uvpe-answer-row__text">
				<input type="text"
					name="answers[<?php echo esc_attr( $index ); ?>][subtitle]"
					value="<?php echo esc_attr( $subtitle ); ?>"
					placeholder="<?php esc_attr_e( 'Subtitle (optional — e.g. position, role, description)', 'unstoppable-voting-polls-elections' ); ?>"
					class="regular-text uvpe-answer-row__sub">
				<div class="uvpe-answer-row__image">
					<input type="hidden"
						name="answers[<?php echo esc_attr( $index ); ?>][image_url]"
						value="<?php echo esc_attr( $image_url ); ?>"
						class="uvpe-answer-row__image-url">
					<button type="button" class="button uvpe-answer-row__image-btn">
						<?php echo $image_url ? esc_html__( 'Replace photo', 'unstoppable-voting-polls-elections' ) : esc_html__( 'Add photo', 'unstoppable-voting-polls-elections' ); ?>
					</button>
					<img src="<?php echo esc_url( $image_url ); ?>" class="uvpe-answer-row__image-preview" alt="" <?php echo $image_url ? '' : 'style="display:none;"'; ?>>
				</div>
			</div>
			<button type="button" class="uvpe-answer-row__remove" aria-label="<?php esc_attr_e( 'Remove option', 'unstoppable-voting-polls-elections' ); ?>">×</button>
		</div>
		<?php
	}
}

$is_new           = empty( $poll );
$poll_id          = $is_new ? 0 : (int) $poll->id;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading a flash-message indicator after a server redirect; no state change.
$msg_type         = isset( $_GET['uvpe_msg'] ) ? sanitize_key( wp_unslash( $_GET['uvpe_msg'] ) ) : '';
$default_settings = get_option( 'uvpe_settings', array(
	'default_template'   => 'classic',
	'default_sort_order' => 'manual',
) );

$name             = $is_new ? '' : $poll->name;
$question         = $is_new ? '' : $poll->question;
$description      = $is_new ? '' : $poll->description;
$status           = $is_new ? 'draft' : $poll->status;
$poll_type        = $is_new ? 'single' : $poll->poll_type;
$max_choices      = $is_new ? 1 : (int) $poll->max_choices;
$template         = $is_new ? $default_settings['default_template'] : $poll->template;
$sort_order       = $is_new ? $default_settings['default_sort_order'] : $poll->sort_order;
$results_display  = $is_new ? 'after_vote' : $poll->results_display;
$results_format   = $is_new ? 'both' : $poll->results_format;
$allow_guests     = $is_new ? 0 : (int) $poll->allow_guests;
$block_by         = $is_new ? 'cookie' : $poll->block_by;
$start_date       = $is_new ? '' : $poll->start_date;
$end_date         = $is_new ? '' : $poll->end_date;
?>
<div class="wrap uvpe-wrap">
	<h1 class="wp-heading-inline">
		<?php echo $is_new ? esc_html__( 'Add New Poll', 'unstoppable-voting-polls-elections' ) : esc_html__( 'Edit Poll', 'unstoppable-voting-polls-elections' ); ?>
	</h1>
	<hr class="wp-header-end">

	<?php if ( $flash && 'success' === $msg_type ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php elseif ( $flash && 'error' === $msg_type ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<form method="post" id="uvpe-poll-form" class="uvpe-poll-form">
		<?php wp_nonce_field( 'uvpe_save_poll', 'uvpe_poll_nonce' ); ?>
		<input type="hidden" name="poll_id" value="<?php echo esc_attr( $poll_id ); ?>">

		<div class="uvpe-form-grid">
			<div class="uvpe-form-main">
				<div class="uvpe-card-panel">
					<label for="uvpe-name" class="uvpe-label"><?php esc_html_e( 'Poll Name', 'unstoppable-voting-polls-elections' ); ?> <span class="required">*</span></label>
					<input type="text" id="uvpe-name" name="name" value="<?php echo esc_attr( $name ); ?>" class="regular-text" required>
					<p class="description"><?php esc_html_e( 'Internal name (only admins see this). E.g. "2026 Officer Elections".', 'unstoppable-voting-polls-elections' ); ?></p>

					<label for="uvpe-question" class="uvpe-label"><?php esc_html_e( 'Question', 'unstoppable-voting-polls-elections' ); ?> <span class="required">*</span></label>
					<input type="text" id="uvpe-question" name="question" value="<?php echo esc_attr( $question ); ?>" class="large-text" required>
					<p class="description"><?php esc_html_e( 'Shown to voters. E.g. "Who should be our 2026 President?"', 'unstoppable-voting-polls-elections' ); ?></p>

					<label for="uvpe-description" class="uvpe-label"><?php esc_html_e( 'Description (optional)', 'unstoppable-voting-polls-elections' ); ?></label>
					<textarea id="uvpe-description" name="description" rows="3" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
				</div>

				<div class="uvpe-card-panel">
					<h2 class="uvpe-card-panel__title"><?php esc_html_e( 'Options', 'unstoppable-voting-polls-elections' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Add the candidates, teams, items, or choices voters can pick. Drag the handle to reorder.', 'unstoppable-voting-polls-elections' ); ?></p>

					<div id="uvpe-answers" class="uvpe-answers">
						<?php
						if ( ! empty( $answers ) ) {
							foreach ( $answers as $i => $answer ) {
								uvpe_admin_print_answer_row( $i, $answer );
							}
						} else {
							for ( $i = 0; $i < 2; $i++ ) {
								uvpe_admin_print_answer_row( $i );
							}
						}
						?>
					</div>

					<button type="button" id="uvpe-add-answer" class="button button-secondary">
						+ <?php esc_html_e( 'Add Option', 'unstoppable-voting-polls-elections' ); ?>
					</button>
				</div>
			</div>

			<aside class="uvpe-form-side">
				<div class="uvpe-card-panel">
					<h2 class="uvpe-card-panel__title"><?php esc_html_e( 'Publish', 'unstoppable-voting-polls-elections' ); ?></h2>

					<label for="uvpe-status" class="uvpe-label"><?php esc_html_e( 'Status', 'unstoppable-voting-polls-elections' ); ?></label>
					<select id="uvpe-status" name="status">
						<?php foreach ( UVPE_Poll::get_statuses() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>

					<label for="uvpe-start" class="uvpe-label"><?php esc_html_e( 'Start date (optional)', 'unstoppable-voting-polls-elections' ); ?></label>
					<input type="datetime-local" id="uvpe-start" name="start_date" value="<?php echo esc_attr( $start_date ? gmdate( 'Y-m-d\TH:i', strtotime( $start_date ) ) : '' ); ?>">

					<label for="uvpe-end" class="uvpe-label"><?php esc_html_e( 'End date (optional)', 'unstoppable-voting-polls-elections' ); ?></label>
					<input type="datetime-local" id="uvpe-end" name="end_date" value="<?php echo esc_attr( $end_date ? gmdate( 'Y-m-d\TH:i', strtotime( $end_date ) ) : '' ); ?>">

					<div class="uvpe-card-panel__actions">
						<button type="submit" name="uvpe_save_poll" value="1" class="button button-primary button-large">
							<?php echo $is_new ? esc_html__( 'Create Poll', 'unstoppable-voting-polls-elections' ) : esc_html__( 'Save Changes', 'unstoppable-voting-polls-elections' ); ?>
						</button>
					</div>

					<?php if ( ! $is_new ) : ?>
						<p class="uvpe-shortcode-block">
							<strong><?php esc_html_e( 'Shortcode:', 'unstoppable-voting-polls-elections' ); ?></strong><br>
							<code>[uvpe_poll id="<?php echo esc_html( $poll_id ); ?>"]</code>
						</p>
					<?php endif; ?>
				</div>

				<div class="uvpe-card-panel">
					<h2 class="uvpe-card-panel__title"><?php esc_html_e( 'Voting Rules', 'unstoppable-voting-polls-elections' ); ?></h2>

					<label class="uvpe-label"><?php esc_html_e( 'Vote type', 'unstoppable-voting-polls-elections' ); ?></label>
					<label class="uvpe-radio">
						<input type="radio" name="poll_type" value="single" <?php checked( $poll_type, 'single' ); ?>>
						<?php esc_html_e( 'Single choice', 'unstoppable-voting-polls-elections' ); ?>
					</label>
					<label class="uvpe-radio">
						<input type="radio" name="poll_type" value="multiple" <?php checked( $poll_type, 'multiple' ); ?>>
						<?php esc_html_e( 'Multiple choice', 'unstoppable-voting-polls-elections' ); ?>
					</label>

					<label for="uvpe-max" class="uvpe-label"><?php esc_html_e( 'Max selections (multi-choice)', 'unstoppable-voting-polls-elections' ); ?></label>
					<input type="number" id="uvpe-max" name="max_choices" min="1" max="50" value="<?php echo esc_attr( $max_choices ); ?>">

					<label class="uvpe-checkbox">
						<input type="checkbox" name="allow_guests" value="1" <?php checked( 1, $allow_guests ); ?>>
						<?php esc_html_e( 'Allow guest (logged-out) voters', 'unstoppable-voting-polls-elections' ); ?>
					</label>

					<label for="uvpe-block" class="uvpe-label"><?php esc_html_e( 'Prevent duplicate voting by', 'unstoppable-voting-polls-elections' ); ?></label>
					<select id="uvpe-block" name="block_by">
						<?php foreach ( UVPE_Poll::get_block_modes() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $block_by, $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="uvpe-card-panel">
					<h2 class="uvpe-card-panel__title"><?php esc_html_e( 'Display', 'unstoppable-voting-polls-elections' ); ?></h2>

					<label for="uvpe-template" class="uvpe-label"><?php esc_html_e( 'Template', 'unstoppable-voting-polls-elections' ); ?></label>
					<select id="uvpe-template" name="template">
						<?php foreach ( UVPE_Poll::get_templates() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $template, $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>

					<label for="uvpe-sort" class="uvpe-label"><?php esc_html_e( 'Sort options by', 'unstoppable-voting-polls-elections' ); ?></label>
					<select id="uvpe-sort" name="sort_order">
						<?php foreach ( UVPE_Poll::get_sort_orders() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $sort_order, $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>

					<label for="uvpe-results-display" class="uvpe-label"><?php esc_html_e( 'Show results', 'unstoppable-voting-polls-elections' ); ?></label>
					<select id="uvpe-results-display" name="results_display">
						<?php foreach ( UVPE_Poll::get_results_display_modes() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $results_display, $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>

					<label for="uvpe-results-format" class="uvpe-label"><?php esc_html_e( 'Results format', 'unstoppable-voting-polls-elections' ); ?></label>
					<select id="uvpe-results-format" name="results_format">
						<option value="both" <?php selected( $results_format, 'both' ); ?>><?php esc_html_e( 'Percent + vote count', 'unstoppable-voting-polls-elections' ); ?></option>
						<option value="percent" <?php selected( $results_format, 'percent' ); ?>><?php esc_html_e( 'Percent only', 'unstoppable-voting-polls-elections' ); ?></option>
						<option value="number" <?php selected( $results_format, 'number' ); ?>><?php esc_html_e( 'Vote count only', 'unstoppable-voting-polls-elections' ); ?></option>
					</select>
				</div>
			</aside>
		</div>
	</form>

	<script type="text/template" id="uvpe-answer-template-html">
		<?php uvpe_admin_print_answer_row( '__INDEX__' ); ?>
	</script>
</div>
