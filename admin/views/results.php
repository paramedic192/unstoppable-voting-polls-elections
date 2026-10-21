<?php
/**
 * Admin View: Poll Results.
 *
 * Variables available: $poll, $answers (sorted by votes_desc).
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local view/template variables, not globals.

$status_labels    = UVPE_Poll::get_statuses();
$templates_labels = UVPE_Poll::get_templates();
$is_open          = UVPE_Poll::is_open( $poll );
?>
<div class="wrap uvpe-wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Results:', 'unstoppable-voting-polls-elections' ); ?>
		<?php echo esc_html( $poll->name ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . UVPE_Admin::MENU_EDIT . '&poll_id=' . $poll->id ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Edit Poll', 'unstoppable-voting-polls-elections' ); ?>
	</a>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . UVPE_Admin::MENU_SLUG ) ); ?>" class="page-title-action">
		<?php esc_html_e( '← All Polls', 'unstoppable-voting-polls-elections' ); ?>
	</a>
	<hr class="wp-header-end">

	<div class="uvpe-card-panel">
		<p><strong><?php esc_html_e( 'Question:', 'unstoppable-voting-polls-elections' ); ?></strong> <?php echo esc_html( $poll->question ); ?></p>
		<?php if ( ! empty( $poll->description ) ) : ?>
			<p><?php echo esc_html( $poll->description ); ?></p>
		<?php endif; ?>

		<p>
			<strong><?php esc_html_e( 'Status:', 'unstoppable-voting-polls-elections' ); ?></strong>
			<span class="uvpe-status uvpe-status--<?php echo esc_attr( $poll->status ); ?>">
				<?php echo esc_html( isset( $status_labels[ $poll->status ] ) ? $status_labels[ $poll->status ] : $poll->status ); ?>
			</span>
			&nbsp;|&nbsp;
			<strong><?php esc_html_e( 'Template:', 'unstoppable-voting-polls-elections' ); ?></strong>
			<?php echo esc_html( isset( $templates_labels[ $poll->template ] ) ? $templates_labels[ $poll->template ] : $poll->template ); ?>
			&nbsp;|&nbsp;
			<strong><?php esc_html_e( 'Total votes:', 'unstoppable-voting-polls-elections' ); ?></strong>
			<?php echo esc_html( number_format_i18n( $poll->total_votes ) ); ?>
			&nbsp;|&nbsp;
			<?php echo $is_open ? '<span style="color: #46b450;">' . esc_html__( 'Accepting votes', 'unstoppable-voting-polls-elections' ) . '</span>' : '<span style="color: #b32d2e;">' . esc_html__( 'Not accepting votes', 'unstoppable-voting-polls-elections' ) . '</span>'; ?>
		</p>
	</div>

	<div class="uvpe-card-panel">
		<h2 class="uvpe-card-panel__title"><?php esc_html_e( 'Results', 'unstoppable-voting-polls-elections' ); ?></h2>

		<?php if ( empty( $answers ) ) : ?>
			<p><?php esc_html_e( 'No options have been added to this poll yet.', 'unstoppable-voting-polls-elections' ); ?></p>
		<?php elseif ( 0 === (int) $poll->total_votes ) : ?>
			<p><?php esc_html_e( 'No votes have been cast yet.', 'unstoppable-voting-polls-elections' ); ?></p>
			<table class="wp-list-table widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Rank', 'unstoppable-voting-polls-elections' ); ?></th>
						<th><?php esc_html_e( 'Option', 'unstoppable-voting-polls-elections' ); ?></th>
						<th><?php esc_html_e( 'Subtitle', 'unstoppable-voting-polls-elections' ); ?></th>
						<th><?php esc_html_e( 'Votes', 'unstoppable-voting-polls-elections' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $answers as $i => $answer ) : ?>
						<tr>
							<td>#<?php echo esc_html( $i + 1 ); ?></td>
							<td><?php echo esc_html( $answer->answer_text ); ?></td>
							<td><?php echo esc_html( $answer->subtitle ); ?></td>
							<td>0</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<table class="wp-list-table widefat striped uvpe-results-table">
				<thead>
					<tr>
						<th style="width: 60px;"><?php esc_html_e( 'Rank', 'unstoppable-voting-polls-elections' ); ?></th>
						<th><?php esc_html_e( 'Option', 'unstoppable-voting-polls-elections' ); ?></th>
						<th style="width: 100px;"><?php esc_html_e( 'Votes', 'unstoppable-voting-polls-elections' ); ?></th>
						<th style="width: 100px;"><?php esc_html_e( '%', 'unstoppable-voting-polls-elections' ); ?></th>
						<th style="width: 30%;"><?php esc_html_e( 'Distribution', 'unstoppable-voting-polls-elections' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $answers as $i => $answer ) :
						$percent = UVPE_Templates::percentage( $answer, $poll->total_votes );
						$is_leader = ( 0 === $i && (int) $answer->vote_count > 0 );
						?>
						<tr<?php echo $is_leader ? ' class="uvpe-leader-row"' : ''; ?>>
							<td><strong>#<?php echo esc_html( $i + 1 ); ?></strong></td>
							<td>
								<strong><?php echo esc_html( $answer->answer_text ); ?></strong>
								<?php if ( ! empty( $answer->subtitle ) ) : ?>
									<br><span style="color: #757575; font-size: 12px;"><?php echo esc_html( $answer->subtitle ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( number_format_i18n( $answer->vote_count ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $percent, 1 ) ); ?>%</td>
							<td>
								<div class="uvpe-admin-bar">
									<div class="uvpe-admin-bar__fill" style="width: <?php echo esc_attr( $percent ); ?>%;"></div>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<tr>
						<th colspan="2"><strong><?php esc_html_e( 'Total', 'unstoppable-voting-polls-elections' ); ?></strong></th>
						<th><strong><?php echo esc_html( number_format_i18n( $poll->total_votes ) ); ?></strong></th>
						<th>100%</th>
						<th></th>
					</tr>
				</tfoot>
			</table>
		<?php endif; ?>
	</div>
</div>
