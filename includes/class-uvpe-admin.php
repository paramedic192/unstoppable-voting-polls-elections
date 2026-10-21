<?php
/**
 * Admin Pages.
 *
 * @package Unstoppable_Voting_Polls_Elections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin menu, pages, asset enqueueing, and form-save handling.
 */
class UVPE_Admin {

	const MENU_SLUG     = 'uvpe-polls';
	const MENU_EDIT     = 'uvpe-edit-poll';
	const MENU_RESULTS  = 'uvpe-results';
	const MENU_SETTINGS = 'uvpe-settings';

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'maybe_handle_form' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_menu_styles' ) );

		// Row action on the Plugins screen.
		add_filter( 'plugin_action_links_' . UVPE_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Add the Unstoppable Voting, Polls & Elections menu.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Voting', 'unstoppable-voting-polls-elections' ),
			__( 'Voting', 'unstoppable-voting-polls-elections' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_polls_list' ),
			'dashicons-yes-alt',
			30
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'All Polls', 'unstoppable-voting-polls-elections' ),
			__( 'All Polls', 'unstoppable-voting-polls-elections' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_polls_list' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Add New Poll', 'unstoppable-voting-polls-elections' ),
			__( 'Add New', 'unstoppable-voting-polls-elections' ),
			'manage_options',
			self::MENU_EDIT,
			array( $this, 'render_poll_edit' )
		);

		// Hidden submenu so the slug works without showing in the menu.
		add_submenu_page(
			'', // Hidden.
			__( 'Poll Results', 'unstoppable-voting-polls-elections' ),
			__( 'Poll Results', 'unstoppable-voting-polls-elections' ),
			'manage_options',
			self::MENU_RESULTS,
			array( $this, 'render_results' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'unstoppable-voting-polls-elections' ),
			__( 'Settings', 'unstoppable-voting-polls-elections' ),
			'manage_options',
			self::MENU_SETTINGS,
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Register a stub stylesheet and attach the admin-menu brand colours as inline CSS.
	 *
	 * Loaded on every admin page so the top-level menu item stays branded as the user navigates,
	 * but uses wp_add_inline_style() (the WordPress-approved pattern) rather than a raw <style> block.
	 */
	public function enqueue_menu_styles() {
		$handle = 'uvpe-admin-menu';
		wp_register_style( $handle, false, array(), UVPE_VERSION );
		wp_enqueue_style( $handle );

		$slug = sanitize_html_class( self::MENU_SLUG );
		$css  = "
			#adminmenu .toplevel_page_{$slug} .wp-menu-name { color: #f0b849; }
			#adminmenu .toplevel_page_{$slug}:hover .wp-menu-name,
			#adminmenu .toplevel_page_{$slug}.current .wp-menu-name,
			#adminmenu .toplevel_page_{$slug}.wp-has-current-submenu .wp-menu-name { color: #fff; }
			#adminmenu .toplevel_page_{$slug} .wp-menu-image:before { color: #f0b849; }
			#adminmenu .toplevel_page_{$slug}:hover .wp-menu-image:before,
			#adminmenu .toplevel_page_{$slug}.current .wp-menu-image:before,
			#adminmenu .toplevel_page_{$slug}.wp-has-current-submenu .wp-menu-image:before { color: #fff; }
		";

		wp_add_inline_style( $handle, $css );
	}

	/**
	 * Add settings link on the Plugins page.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$settings = '<a href="' . esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ) . '">' . esc_html__( 'Polls', 'unstoppable-voting-polls-elections' ) . '</a>';
		array_unshift( $links, $settings );
		return $links;
	}

	/**
	 * Enqueue admin assets only on our pages.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		$our_hooks = array(
			'toplevel_page_' . self::MENU_SLUG,
			'voting_page_' . self::MENU_EDIT,
			'voting_page_' . self::MENU_RESULTS,
			'voting_page_' . self::MENU_SETTINGS,
			'admin_page_' . self::MENU_RESULTS, // for hidden submenu.
		);

		$is_our_screen = false;
		foreach ( $our_hooks as $h ) {
			if ( $hook === $h || false !== strpos( $hook, 'uvpe-' ) ) {
				$is_our_screen = true;
				break;
			}
		}

		if ( ! $is_our_screen ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'uvpe-admin',
			UVPE_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			UVPE_VERSION
		);

		wp_enqueue_script(
			'uvpe-admin',
			UVPE_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			UVPE_VERSION,
			true
		);

		wp_localize_script(
			'uvpe-admin',
			'uvpe_admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'uvpe_admin_nonce' ),
				'i18n'     => array(
					'confirm_delete'    => __( 'Are you sure you want to delete this poll? This cannot be undone.', 'unstoppable-voting-polls-elections' ),
					'choose_image'      => __( 'Choose image', 'unstoppable-voting-polls-elections' ),
					'use_image'         => __( 'Use this image', 'unstoppable-voting-polls-elections' ),
					'remove'            => __( 'Remove', 'unstoppable-voting-polls-elections' ),
					'must_have_answers' => __( 'Please add at least two options before saving.', 'unstoppable-voting-polls-elections' ),
				),
			)
		);
	}

	/* ------------------------------------------------------------------
	 * Form save handling
	 * ------------------------------------------------------------------ */

	/**
	 * If a poll-edit form was POSTed, sanitize and save it.
	 */
	public function maybe_handle_form() {
		if ( empty( $_POST['uvpe_save_poll'] ) ) {
			return;
		}

		// Permission check first.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'unstoppable-voting-polls-elections' ) );
		}

		// Nonce check.
		if ( ! isset( $_POST['uvpe_poll_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['uvpe_poll_nonce'] ) ), 'uvpe_save_poll' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'unstoppable-voting-polls-elections' ) );
		}

		$poll_id = isset( $_POST['poll_id'] ) ? absint( wp_unslash( $_POST['poll_id'] ) ) : 0;

		// Sanitize basic poll fields.
		$data = $this->sanitize_poll_data( $_POST );

		// Validate name and question (sanitize cleans, validate rejects).
		if ( '' === $data['name'] ) {
			$this->redirect_with_message( 'error', __( 'Poll name is required.', 'unstoppable-voting-polls-elections' ), $poll_id );
		}
		if ( '' === $data['question'] ) {
			$this->redirect_with_message( 'error', __( 'Poll question is required.', 'unstoppable-voting-polls-elections' ), $poll_id );
		}

		// Sanitize answer rows.
		$answers = $this->sanitize_answers( $_POST );

		// Strict whitelisting (no mass assignment): only allowed values made it
		// into $data via sanitize_poll_data(). Same for answers.

		if ( $poll_id > 0 ) {
			$result = UVPE_Poll::update( $poll_id, $data );
		} else {
			$result = UVPE_Poll::create( $data );
			if ( ! is_wp_error( $result ) ) {
				$poll_id = (int) $result;
			}
		}

		if ( is_wp_error( $result ) ) {
			$this->redirect_with_message( 'error', $result->get_error_message(), $poll_id );
		}

		UVPE_Poll::save_answers( $poll_id, $answers );

		$this->redirect_with_message( 'success', __( 'Poll saved.', 'unstoppable-voting-polls-elections' ), $poll_id );
	}

	/**
	 * Sanitize the poll fields from $_POST.
	 *
	 * @param array $post Raw $_POST.
	 * @return array
	 */
	private function sanitize_poll_data( $post ) {
		$templates  = array_keys( UVPE_Poll::get_templates() );
		$sort_keys  = array_keys( UVPE_Poll::get_sort_orders() );
		$statuses   = array_keys( UVPE_Poll::get_statuses() );
		$res_modes  = array_keys( UVPE_Poll::get_results_display_modes() );
		$block_keys = array_keys( UVPE_Poll::get_block_modes() );

		$post = wp_unslash( $post );

		$data = array(
			'name'         => isset( $post['name'] ) ? sanitize_text_field( $post['name'] ) : '',
			'question'     => isset( $post['question'] ) ? sanitize_text_field( $post['question'] ) : '',
			'description' => isset( $post['description'] ) ? sanitize_textarea_field( $post['description'] ) : '',
			'status'       => isset( $post['status'] ) ? sanitize_key( $post['status'] ) : 'draft',
			'poll_type'    => isset( $post['poll_type'] ) ? sanitize_key( $post['poll_type'] ) : 'single',
			'max_choices'  => isset( $post['max_choices'] ) ? absint( $post['max_choices'] ) : 1,
			'template'     => isset( $post['template'] ) ? sanitize_key( $post['template'] ) : 'classic',
			'sort_order'   => isset( $post['sort_order'] ) ? sanitize_key( $post['sort_order'] ) : 'manual',
			'results_display' => isset( $post['results_display'] ) ? sanitize_key( $post['results_display'] ) : 'after_vote',
			'results_format'  => isset( $post['results_format'] ) ? sanitize_key( $post['results_format'] ) : 'both',
			'allow_guests'         => ! empty( $post['allow_guests'] ) ? 1 : 0,
			'block_by'             => isset( $post['block_by'] ) ? sanitize_key( $post['block_by'] ) : 'cookie',
			'vote_revision_window' => isset( $post['vote_revision_window'] ) ? absint( $post['vote_revision_window'] ) : 0,
			'start_date'           => $this->sanitize_datetime( isset( $post['start_date'] ) ? $post['start_date'] : '' ),
			'end_date'             => $this->sanitize_datetime( isset( $post['end_date'] ) ? $post['end_date'] : '' ),
		);

		// Strict whitelist enforcement.
		if ( ! in_array( $data['status'], $statuses, true ) ) {
			$data['status'] = 'draft';
		}
		if ( ! in_array( $data['poll_type'], array( 'single', 'multiple' ), true ) ) {
			$data['poll_type'] = 'single';
		}
		if ( $data['max_choices'] < 1 ) {
			$data['max_choices'] = 1;
		}
		if ( $data['max_choices'] > 50 ) {
			$data['max_choices'] = 50;
		}
		if ( 'single' === $data['poll_type'] ) {
			$data['max_choices'] = 1;
		}
		if ( ! in_array( $data['template'], $templates, true ) ) {
			$data['template'] = 'classic';
		}
		if ( ! in_array( $data['sort_order'], $sort_keys, true ) ) {
			$data['sort_order'] = 'manual';
		}
		if ( ! in_array( $data['results_display'], $res_modes, true ) ) {
			$data['results_display'] = 'after_vote';
		}
		if ( ! in_array( $data['results_format'], array( 'number', 'percent', 'both' ), true ) ) {
			$data['results_format'] = 'both';
		}
		if ( ! in_array( $data['block_by'], $block_keys, true ) ) {
			$data['block_by'] = 'cookie';
		}
		// Clamp vote-revision window (minutes). 0 = disabled, hard max 1 week.
		if ( $data['vote_revision_window'] < 0 ) {
			$data['vote_revision_window'] = 0;
		}
		if ( $data['vote_revision_window'] > 10080 ) {
			$data['vote_revision_window'] = 10080;
		}

		return $data;
	}

	/**
	 * Sanitize the answer rows from $_POST.
	 *
	 * @param array $post Raw $_POST.
	 * @return array
	 */
	private function sanitize_answers( $post ) {
		$post = wp_unslash( $post );
		$raw  = isset( $post['answers'] ) && is_array( $post['answers'] ) ? $post['answers'] : array();

		$answers = array();
		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$text = isset( $row['text'] ) ? sanitize_text_field( $row['text'] ) : '';
			if ( '' === $text ) {
				continue;
			}
			$answers[] = array(
				'id'        => isset( $row['id'] ) ? absint( $row['id'] ) : 0,
				'text'      => $text,
				'subtitle'  => isset( $row['subtitle'] ) ? sanitize_text_field( $row['subtitle'] ) : '',
				'image_url' => isset( $row['image_url'] ) ? esc_url_raw( $row['image_url'] ) : '',
			);
		}
		return $answers;
	}

	/**
	 * Normalise a datetime-local input into MySQL datetime, or empty string.
	 *
	 * @param string $value Raw input.
	 * @return string
	 */
	private function sanitize_datetime( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return null;
		}
		$ts = strtotime( $value );
		if ( false === $ts ) {
			return null;
		}
		return gmdate( 'Y-m-d H:i:s', $ts );
	}

	/**
	 * Redirect back to the edit screen with a status flag.
	 *
	 * @param string $type    'success' or 'error'.
	 * @param string $message Message (unused for now — passed via query string).
	 * @param int    $poll_id Poll ID.
	 */
	private function redirect_with_message( $type, $message, $poll_id = 0 ) {
		$url = add_query_arg(
			array(
				'page'     => self::MENU_EDIT,
				'poll_id'  => $poll_id ? absint( $poll_id ) : 0,
				'uvpe_msg' => $type,
			),
			admin_url( 'admin.php' )
		);
		// Stash the message in a one-shot transient keyed to the user.
		set_transient( 'uvpe_admin_msg_' . get_current_user_id(), $message, 60 );
		wp_safe_redirect( $url );
		exit;
	}

	/* ------------------------------------------------------------------
	 * Page render dispatchers
	 * ------------------------------------------------------------------ */

	/**
	 * Render the polls list page.
	 */
	public function render_polls_list() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'unstoppable-voting-polls-elections' ) );
		}

		// Handle a GET-based delete link (with nonce).
		if ( isset( $_GET['action'], $_GET['poll_id'] ) && 'delete' === $_GET['action'] ) {
			$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
			$poll_id = absint( wp_unslash( $_GET['poll_id'] ) );
			if ( wp_verify_nonce( $nonce, 'uvpe_delete_poll_' . $poll_id ) ) {
				UVPE_Poll::delete( $poll_id );
				set_transient( 'uvpe_admin_msg_' . get_current_user_id(), __( 'Poll deleted.', 'unstoppable-voting-polls-elections' ), 60 );
				wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG . '&uvpe_msg=success' ) );
				exit;
			}
		}

		// Handle a GET-based duplicate link (with nonce).
		if ( isset( $_GET['action'], $_GET['poll_id'] ) && 'duplicate' === $_GET['action'] ) {
			$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
			$poll_id = absint( wp_unslash( $_GET['poll_id'] ) );
			if ( wp_verify_nonce( $nonce, 'uvpe_duplicate_poll_' . $poll_id ) ) {
				$new_id = UVPE_Poll::duplicate( $poll_id );
				if ( is_wp_error( $new_id ) ) {
					set_transient(
						'uvpe_admin_msg_' . get_current_user_id(),
						$new_id->get_error_message(),
						60
					);
					wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG . '&uvpe_msg=error' ) );
				} else {
					set_transient(
						'uvpe_admin_msg_' . get_current_user_id(),
						__( 'Poll duplicated — edit the copy to adjust it.', 'unstoppable-voting-polls-elections' ),
						60
					);
					// Jump straight to the new draft so the user can tweak it.
					wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_EDIT . '&poll_id=' . (int) $new_id . '&uvpe_msg=success' ) );
				}
				exit;
			}
		}

		$search       = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$status       = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$polls        = UVPE_Poll::get_all( array( 'search' => $search, 'status' => $status, 'limit' => 100 ) );
		$total_polls  = UVPE_Poll::count();
		$active_count = UVPE_Poll::count( 'active' );
		$draft_count  = UVPE_Poll::count( 'draft' );
		$closed_count = UVPE_Poll::count( 'closed' );

		$flash = get_transient( 'uvpe_admin_msg_' . get_current_user_id() );
		if ( $flash ) {
			delete_transient( 'uvpe_admin_msg_' . get_current_user_id() );
		}

		require UVPE_PLUGIN_DIR . 'admin/views/polls-list.php';
	}

	/**
	 * Render the poll edit page.
	 */
	public function render_poll_edit() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'unstoppable-voting-polls-elections' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page render; nonce required only on state-changing actions handled elsewhere.
		$poll_id = isset( $_GET['poll_id'] ) ? absint( wp_unslash( $_GET['poll_id'] ) ) : 0;
		$poll    = $poll_id > 0 ? UVPE_Poll::get( $poll_id ) : null;
		$answers = $poll ? UVPE_Poll::get_answers( $poll->id, 'manual' ) : array();

		$flash = get_transient( 'uvpe_admin_msg_' . get_current_user_id() );
		if ( $flash ) {
			delete_transient( 'uvpe_admin_msg_' . get_current_user_id() );
		}

		require UVPE_PLUGIN_DIR . 'admin/views/poll-edit.php';
	}

	/**
	 * Render the poll results page.
	 */
	public function render_results() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'unstoppable-voting-polls-elections' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page render; nonce required only on state-changing actions handled elsewhere.
		$poll_id = isset( $_GET['poll_id'] ) ? absint( wp_unslash( $_GET['poll_id'] ) ) : 0;
		$poll    = $poll_id > 0 ? UVPE_Poll::get( $poll_id ) : null;

		if ( ! $poll ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Poll Results', 'unstoppable-voting-polls-elections' ) . '</h1>';
			echo '<p>' . esc_html__( 'Poll not found.', 'unstoppable-voting-polls-elections' ) . '</p></div>';
			return;
		}

		$answers = UVPE_Poll::get_answers( $poll->id, 'votes_desc' );

		require UVPE_PLUGIN_DIR . 'admin/views/results.php';
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'unstoppable-voting-polls-elections' ) );
		}

		// Save settings form.
		if ( ! empty( $_POST['uvpe_save_settings'] ) ) {
			if ( ! isset( $_POST['uvpe_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['uvpe_settings_nonce'] ) ), 'uvpe_save_settings' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'unstoppable-voting-polls-elections' ) );
			}

			$templates = array_keys( UVPE_Poll::get_templates() );
			$sorts     = array_keys( UVPE_Poll::get_sort_orders() );

			$post = wp_unslash( $_POST );

			$default_template = isset( $post['default_template'] ) ? sanitize_key( $post['default_template'] ) : 'classic';
			$default_sort     = isset( $post['default_sort_order'] ) ? sanitize_key( $post['default_sort_order'] ) : 'manual';
			$brand_color      = isset( $post['brand_color'] ) ? sanitize_hex_color( $post['brand_color'] ) : '#f0b849';

			if ( ! in_array( $default_template, $templates, true ) ) {
				$default_template = 'classic';
			}
			if ( ! in_array( $default_sort, $sorts, true ) ) {
				$default_sort = 'manual';
			}
			if ( empty( $brand_color ) ) {
				$brand_color = '#f0b849';
			}

			update_option( 'uvpe_settings', array(
				'default_template'   => $default_template,
				'default_sort_order' => $default_sort,
				'brand_color'        => $brand_color,
			) );

			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'unstoppable-voting-polls-elections' ) . '</p></div>';
		}

		$settings = get_option( 'uvpe_settings', array(
			'default_template'   => 'classic',
			'default_sort_order' => 'manual',
			'brand_color'        => '#f0b849',
		) );

		require UVPE_PLUGIN_DIR . 'admin/views/settings.php';
	}
}
