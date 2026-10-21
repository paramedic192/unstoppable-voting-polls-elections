<?php
/**
 * Admin View: Settings.
 *
 * Variables available: $settings
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local view/template variables, not globals.
?>
<div class="wrap uvpe-wrap">
	<h1><?php esc_html_e( 'Voting Settings', 'unstoppable-voting-polls-elections' ); ?></h1>

	<form method="post" class="uvpe-settings-form">
		<?php wp_nonce_field( 'uvpe_save_settings', 'uvpe_settings_nonce' ); ?>

		<div class="uvpe-card-panel">
			<h2 class="uvpe-card-panel__title"><?php esc_html_e( 'Defaults for New Polls', 'unstoppable-voting-polls-elections' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'These defaults are pre-selected when you create a new poll. You can override them per poll.', 'unstoppable-voting-polls-elections' ); ?>
			</p>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="default_template"><?php esc_html_e( 'Default template', 'unstoppable-voting-polls-elections' ); ?></label>
						</th>
						<td>
							<select id="default_template" name="default_template">
								<?php foreach ( UVPE_Poll::get_templates() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['default_template'], $key ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Pick whichever template fits most of your votes — usually Official Ballot for elections or Candidate Cards for visual votes.', 'unstoppable-voting-polls-elections' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="default_sort_order"><?php esc_html_e( 'Default option order', 'unstoppable-voting-polls-elections' ); ?></label>
						</th>
						<td>
							<select id="default_sort_order" name="default_sort_order">
								<?php foreach ( UVPE_Poll::get_sort_orders() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['default_sort_order'], $key ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="brand_color"><?php esc_html_e( 'Accent colour', 'unstoppable-voting-polls-elections' ); ?></label>
						</th>
						<td>
							<input type="text"
								id="brand_color"
								name="brand_color"
								value="<?php echo esc_attr( $settings['brand_color'] ); ?>"
								class="regular-text"
								placeholder="#f0b849"
								pattern="^#[a-fA-F0-9]{3,8}$">
							<p class="description"><?php esc_html_e( 'Hex color used for poll buttons and result bars on the front end. Defaults to brand gold (#f0b849).', 'unstoppable-voting-polls-elections' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<p class="submit">
			<button type="submit" name="uvpe_save_settings" value="1" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'unstoppable-voting-polls-elections' ); ?>
			</button>
		</p>
	</form>

	<div class="uvpe-card-panel">
		<h2 class="uvpe-card-panel__title"><?php esc_html_e( 'About', 'unstoppable-voting-polls-elections' ); ?></h2>
		<p>
			<strong><?php esc_html_e( 'Unstoppable Voting, Polls & Elections', 'unstoppable-voting-polls-elections' ); ?></strong>
			<?php
			printf(
				/* translators: %s: plugin version. */
				esc_html__( 'v%s — built by Unstoppable Solutions.', 'unstoppable-voting-polls-elections' ),
				esc_html( UVPE_VERSION )
			);
			?>
		</p>
		<p>
			<?php esc_html_e( 'Need help? Visit', 'unstoppable-voting-polls-elections' ); ?>
			<a href="https://unstoppablesolutions.app" target="_blank" rel="noopener noreferrer">unstoppablesolutions.app</a>.
		</p>
	</div>
</div>
