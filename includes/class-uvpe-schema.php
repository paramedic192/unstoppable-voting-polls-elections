<?php
/**
 * Database Schema Handler.
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates and manages the plugin's database tables.
 */
class UVPE_Schema {

	/**
	 * Current schema version. Bump this when table definitions change.
	 */
	const SCHEMA_VERSION = 2;

	/**
	 * Get the prefixed table name.
	 *
	 * @param string $table Bare table name (e.g. "polls").
	 * @return string Full table name with prefix.
	 */
	public static function table( $table ) {
		global $wpdb;
		return $wpdb->prefix . UVPE_TABLE_PREFIX . $table;
	}

	/**
	 * Register the core tables and expose them to Pro / third-party add-ons.
	 *
	 * Add-ons can register extra tables by filtering `uvpe_schema_tables` and
	 * returning additional {name, sql} entries.
	 *
	 * @return array
	 */
	public static function get_tables() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$polls   = self::table( 'polls' );
		$answers = self::table( 'answers' );
		$votes   = self::table( 'votes' );

		$tables = array(
			'polls'   => "CREATE TABLE {$polls} (
	id bigint(20) NOT NULL AUTO_INCREMENT,
	name varchar(255) NOT NULL,
	question text NOT NULL,
	description text NOT NULL,
	status varchar(20) NOT NULL DEFAULT 'draft',
	poll_type varchar(20) NOT NULL DEFAULT 'single',
	max_choices int(11) NOT NULL DEFAULT 1,
	template varchar(50) NOT NULL DEFAULT 'classic',
	sort_order varchar(30) NOT NULL DEFAULT 'manual',
	results_display varchar(20) NOT NULL DEFAULT 'after_vote',
	results_format varchar(20) NOT NULL DEFAULT 'both',
	allow_guests tinyint(1) NOT NULL DEFAULT 0,
	block_by varchar(20) NOT NULL DEFAULT 'cookie',
	vote_revision_window int(11) NOT NULL DEFAULT 0,
	start_date datetime DEFAULT NULL,
	end_date datetime DEFAULT NULL,
	total_votes bigint(20) NOT NULL DEFAULT 0,
	author bigint(20) NOT NULL DEFAULT 0,
	added_date datetime NOT NULL,
	modified_date datetime NOT NULL,
	PRIMARY KEY  (id),
	KEY status (status),
	KEY author (author)
) {$charset};",
			'answers' => "CREATE TABLE {$answers} (
	id bigint(20) NOT NULL AUTO_INCREMENT,
	poll_id bigint(20) NOT NULL,
	answer_text varchar(500) NOT NULL,
	subtitle varchar(255) NOT NULL DEFAULT '',
	image_url varchar(500) NOT NULL DEFAULT '',
	sort_position int(11) NOT NULL DEFAULT 0,
	vote_count bigint(20) NOT NULL DEFAULT 0,
	added_date datetime NOT NULL,
	PRIMARY KEY  (id),
	KEY poll_id (poll_id)
) {$charset};",
			'votes'   => "CREATE TABLE {$votes} (
	id bigint(20) NOT NULL AUTO_INCREMENT,
	poll_id bigint(20) NOT NULL,
	answer_id bigint(20) NOT NULL,
	user_id bigint(20) NOT NULL DEFAULT 0,
	ip_address varchar(45) NOT NULL DEFAULT '',
	tracking_id varchar(64) NOT NULL DEFAULT '',
	added_date datetime NOT NULL,
	revised_at datetime DEFAULT NULL,
	PRIMARY KEY  (id),
	KEY poll_id (poll_id),
	KEY answer_id (answer_id),
	KEY user_id (user_id),
	KEY tracking_id (tracking_id)
) {$charset};",
		);

		/**
		 * Filter the schema definitions.
		 *
		 * Add-ons may add additional {name => CREATE TABLE SQL} entries. The
		 * core three keys ("polls", "answers", "votes") should not be changed
		 * by third parties — they belong to the free plugin's data model.
		 *
		 * @since 2.1.0
		 *
		 * @param array $tables Map of table name => dbDelta-compatible SQL.
		 */
		return apply_filters( 'uvpe_schema_tables', $tables );
	}

	/**
	 * Create or upgrade the database tables.
	 */
	public static function create_tables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$sql = implode( "\n", self::get_tables() );
		dbDelta( $sql );
		update_option( 'uvpe_schema_version', self::SCHEMA_VERSION );
	}

	/**
	 * Run any pending migrations.
	 *
	 * Called on every plugin load. Compares stored schema version to the
	 * current constant and re-runs dbDelta if an upgrade is pending.
	 */
	public static function maybe_upgrade() {
		$installed = (int) get_option( 'uvpe_schema_version', 1 );
		if ( $installed >= self::SCHEMA_VERSION ) {
			return;
		}
		self::create_tables();
		/**
		 * Fires after a schema upgrade completes.
		 *
		 * @since 2.1.0
		 *
		 * @param int $from Previous installed version.
		 * @param int $to   New schema version.
		 */
		do_action( 'uvpe_schema_upgraded', $installed, self::SCHEMA_VERSION );
	}

	/**
	 * Drop all plugin tables (used on uninstall).
	 */
	public static function drop_tables() {
		global $wpdb;
		$tables = array( 'votes', 'answers', 'polls' );

		/**
		 * Filter the list of table bare-names to drop on uninstall.
		 *
		 * Add-ons that registered tables via `uvpe_schema_tables` should
		 * append their table names here so their data is also removed.
		 *
		 * @since 2.1.0
		 *
		 * @param array $tables Bare table names (without prefix).
		 */
		$tables = apply_filters( 'uvpe_schema_drop_tables', $tables );

		foreach ( $tables as $t ) {
			$table = self::table( $t );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table identifier is built from $wpdb->prefix and a hard-coded plugin prefix; schema change is intentional in uninstall path.
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}
	}
}
