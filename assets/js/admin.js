/**
 * Unstoppable Voting, Polls & Elections — Admin JS
 *
 * - Add / remove option rows
 * - Drag-to-reorder
 * - Media-library picker for option images
 * - Delete confirmation
 * - Notice dismissal
 */
( function ( $ ) {
	'use strict';

	$( function () {
		initAnswerRows();
		initDeleteConfirm();
		initNoticeDismissal();
	} );

	function initAnswerRows() {
		var $list = $( '#uvpe-answers' );
		if ( ! $list.length ) {
			return;
		}

		// Sortable.
		if ( $.fn.sortable ) {
			$list.sortable( {
				handle: '.uvpe-answer-row__handle',
				placeholder: 'uvpe-answer-row ui-sortable-placeholder',
				forcePlaceholderSize: true,
				update: function () {
					reindexRows();
				}
			} );
		}

		// Add Option button.
		$( '#uvpe-add-answer' ).on( 'click', function ( e ) {
			e.preventDefault();
			addAnswerRow();
		} );

		// Delegated handlers (work for dynamically added rows).
		$list.on( 'click', '.uvpe-answer-row__remove', function () {
			$( this ).closest( '.uvpe-answer-row' ).remove();
			reindexRows();
		} );

		$list.on( 'click', '.uvpe-answer-row__image-btn', function ( e ) {
			e.preventDefault();
			openMediaPicker( $( this ) );
		} );

		// Form-submit validation: at least 2 filled-in rows.
		$( '#uvpe-poll-form' ).on( 'submit', function ( e ) {
			var filled = 0;
			$( '#uvpe-answers .uvpe-answer-row__text' ).each( function () {
				if ( $.trim( $( this ).val() ) !== '' ) {
					filled++;
				}
			} );
			if ( filled < 2 ) {
				e.preventDefault();
				window.alert( ( window.uvpe_admin && window.uvpe_admin.i18n && window.uvpe_admin.i18n.must_have_answers ) || 'Please add at least two options.' );
				return false;
			}
		} );
	}

	function addAnswerRow() {
		var $list = $( '#uvpe-answers' );
		var nextIndex = $list.find( '.uvpe-answer-row' ).length;
		var template = $( '#uvpe-answer-template-html' ).html();
		if ( ! template ) {
			return;
		}
		var html = template.replace( /__INDEX__/g, nextIndex );
		$list.append( html );
		reindexRows();
	}

	function reindexRows() {
		$( '#uvpe-answers .uvpe-answer-row' ).each( function ( newIndex ) {
			var $row = $( this );
			$row.attr( 'data-index', newIndex );
			$row.find( 'input, textarea, select' ).each( function () {
				var $input = $( this );
				var name = $input.attr( 'name' );
				if ( ! name ) {
					return;
				}
				// Convert answers[<anything>][field] -> answers[newIndex][field]
				var rewritten = name.replace( /answers\[[^\]]*\]/, 'answers[' + newIndex + ']' );
				$input.attr( 'name', rewritten );
			} );
		} );
	}

	function openMediaPicker( $button ) {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}
		var frame = window.wp.media( {
			title: ( window.uvpe_admin && window.uvpe_admin.i18n && window.uvpe_admin.i18n.choose_image ) || 'Choose image',
			button: { text: ( window.uvpe_admin && window.uvpe_admin.i18n && window.uvpe_admin.i18n.use_image ) || 'Use this image' },
			multiple: false,
			library: { type: 'image' }
		} );

		frame.on( 'select', function () {
			var att = frame.state().get( 'selection' ).first().toJSON();
			var $row = $button.closest( '.uvpe-answer-row' );
			$row.find( '.uvpe-answer-row__image-url' ).val( att.url );
			var $preview = $row.find( '.uvpe-answer-row__image-preview' );
			$preview.attr( 'src', att.url ).show();
			$button.text( 'Replace photo' );
		} );

		frame.open();
	}

	function initDeleteConfirm() {
		$( document ).on( 'click', '.uvpe-delete-link', function ( e ) {
			var msg = ( window.uvpe_admin && window.uvpe_admin.i18n && window.uvpe_admin.i18n.confirm_delete ) || 'Are you sure?';
			if ( ! window.confirm( msg ) ) {
				e.preventDefault();
				return false;
			}
		} );
	}

	function initNoticeDismissal() {
		$( document ).on( 'click', '.uvpe-notice .notice-dismiss', function () {
			var notice = $( this ).closest( '.uvpe-notice' ).data( 'notice' );
			if ( ! notice || ! window.uvpe_admin ) {
				return;
			}
			$.post( window.uvpe_admin.ajax_url, {
				action: 'uvpe_dismiss_notice',
				notice: notice,
				nonce: window.uvpe_admin.nonce
			} );
		} );
	}
} )( jQuery );
