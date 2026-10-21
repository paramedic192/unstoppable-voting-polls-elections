<?php
/**
 * Admin View: All Polls.
 *
 * Variables available:
 *   $polls, $total_polls, $active_count, $draft_count, $closed_count,
 *   $search, $status, $flash
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local view/template variables, not globals.

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading a flash-message indicator after a server redirect; no state change.
$msg_type = isset( $_GET['uvpe_msg'] ) ? sanitize_key( wp_unslash( $_GET['uvpe_msg'] ) ) : '';
?>
<div class="wrap uvpe-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Voting', 'unstoppable-voting-polls-elections' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . UVPE_Admin::MENU_EDIT ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New', 'unstoppable-voting-polls-elections' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( $flash && 'success' === $msg_type ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php elseif ( $flash && 'error' === $msg_type ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $flash ); ?></p></div>
	<?php endif; ?>

	<div class="uvpe-stats">
		<div class="uvpe-stat-card">
			<span class="uvpe-stat-card__label"><?php esc_html_e( 'Total polls', 'unstoppable-voting-polls-elections' ); ?></span>
			<span class="uvpe-stat-card__value"><?php echo esc_html( number_format_i18n( $total_polls ) ); ?></span>
		</div>
		<div class="uvpe-stat-card uvpe-stat-card--active">
			<span class="uvpe-stat-card__label"><?php esc_html_e( 'Active', 'unstoppable-voting-polls-elections' ); ?></span>
			<span class="uvpe-stat-card__value"><?php echo esc_html( number_format_i18n( $active_count ) ); ?></span>
		</div>
		<div class="uvpe-stat-card">
			<span class="uvpe-stat-card__label"><?php esc_html_e( 'Drafts', 'unstoppable-voting-polls-elections' ); ?></span>
			<span class="uvpe-stat-card__value"><?php echo esc_html( number_format_i18n( $draft_count ) ); ?></span>
		</div>
		<div class="uvpe-stat-card">
			<span class="uvpe-stat-card__label"><?php esc_html_e( 'Closed', 'unstoppable-voting-polls-elections' ); ?></span>
			<span class="uvpe-stat-card__value"><?php echo esc_html( number_format_i18n( $closed_count ) ); ?></span>
		</div>
	</div>

	<form method="get" class="uvpe-filters">
		<input type="hidden" name="page" value="<?php echo esc_attr( UVPE_Admin::MENU_SLUG ); ?>">
		<select name="status">
			<option value=""><?php esc_html_e( 'All statuses', 'unstoppable-voting-polls-elections' ); ?></option>
			<?php foreach ( UVPE_Poll::get_statuses() as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search polls…', 'unstoppable-voting-polls-elections' ); ?>">
		<button type="submit" class="button"><?php esc_html_e( 'Filter', 'unstoppable-voting-polls-elections' ); ?></button>
	</form>

	<table class="wp-list-table widefat fixed striped uvpe-polls-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'unstoppable-voting-polls-elections' ); ?></th>
				<th><?php esc_html_e( 'Question', 'unstoppable-voting-polls-elections' ); ?></th>
				<th><?php esc_html_e( 'Status', 'unstoppable-voting-polls-elections' ); ?></th>
				<th><?php esc_html_e( 'Template', 'unstoppable-voting-polls-elections' ); ?></th>
				<th><?php esc_html_e( 'Votes', 'unstoppable-voting-polls-elections' ); ?></th>
				<th><?php esc_html_e( 'Shortcode', 'unstoppable-voting-polls-elections' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $polls ) ) : ?>
				<tr>
					<td colspan="6">
						<?php esc_html_e( 'No polls yet.', 'unstoppable-voting-polls-elections' ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . UVPE_Admin::MENU_EDIT ) ); ?>">
							<?php esc_html_e( 'Create your first poll →', 'unstoppable-voting-polls-elections' ); ?>
						</a>
					</td>
				</tr>
			<?php else : ?>
				<?php
				$templates_labels = UVPE_Poll::get_templates();
				$status_labels    = UVPE_Poll::get_statuses();
				foreach ( $polls as $poll ) :
					$edit_url    = admin_url( 'admin.php?page=' . UVPE_Admin::MENU_EDIT . '&poll_id=' . $poll->id );
					$results_url = admin_url( 'admin.php?page=' . UVPE_Admin::MENU_RESULTS . '&poll_id=' . $poll->id );
					$delete_url  = wp_nonce_url(
						admin_url( 'admin.php?page=' . UVPE_Admin::MENU_SLUG . '&action=delete&poll_id=' . $poll->id ),
						'uvpe_delete_poll_' . $poll->id
					);
					$shortcode   = '[uvpe_poll id="' . $poll->id . '"]';
					?>
					<tr>
						<td>
							<strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $poll->name ); ?></a></strong>
							<div class="row-actions">
								<span><a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'unstoppable-voting-polls-elections' ); ?></a> | </span>
								<span><a href="<?php echo esc_url( $results_url ); ?>"><?php esc_html_e( 'Results', 'unstoppable-voting-polls-elections' ); ?></a> | </span>
								<span class="delete"><a href="<?php echo esc_url( $delete_url ); ?>" class="uvpe-delete-link"><?php esc_html_e( 'Delete', 'unstoppable-voting-polls-elections' ); ?></a></span>
							</div>
						</td>
						<td><?php echo esc_html( wp_trim_words( $poll->question, 12 ) ); ?></td>
						<td>
							<span class="uvpe-status uvpe-status--<?php echo esc_attr( $poll->status ); ?>">
								<?php echo esc_html( isset( $status_labels[ $poll->status ] ) ? $status_labels[ $poll->status ] : $poll->status ); ?>
							</span>
						</td>
						<td><?php echo esc_html( isset( $templates_labels[ $poll->template ] ) ? $templates_labels[ $poll->template ] : $poll->template ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $poll->total_votes ) ); ?></td>
						<td><code class="uvpe-shortcode"><?php echo esc_html( $shortcode ); ?></code></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
