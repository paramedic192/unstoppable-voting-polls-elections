/**
 * Unstoppable Voting, Polls & Elections — Frontend JS
 *
 * Handles AJAX vote submission with proper error feedback.
 */
( function ( $ ) {
	'use strict';

	$( function () {
		// Delegate so AJAX-replaced poll markup still works.
		$( document ).on( 'submit', '.uvpe-form', handleSubmit );
	} );

	function handleSubmit( e ) {
		e.preventDefault();

		var $form    = $( this );
		var $button  = $form.find( '.uvpe-form__submit' );
		var $message = $form.find( '.uvpe-form__message' );

		// Make sure the user actually picked something.
		var $checked = $form.find( 'input[name="answer_ids[]"]:checked' );
		if ( $checked.length === 0 ) {
			showMessage( $message, ( window.uvpe_frontend.i18n.no_selection || 'Please select an option.' ), 'error' );
			return;
		}

		$button.prop( 'disabled', true );
		$button.data( 'original-text', $button.text() ).text( window.uvpe_frontend.i18n.submitting );
		showMessage( $message, '', '' );

		var data = $form.serialize() + '&action=uvpe_submit_vote';

		$.ajax( {
			url: window.uvpe_frontend.ajax_url,
			type: 'POST',
			data: data,
			dataType: 'json'
		} )
			.done( function ( response ) {
				if ( response && response.success && response.data && response.data.html ) {
					// Replace the whole poll with the freshly rendered version (which now shows results).
					var $poll = $form.closest( '.uvpe-poll' );
					var $newPoll = $( response.data.html );
					$poll.replaceWith( $newPoll );
					$newPoll.hide().fadeIn( 200 );
				} else {
					var errMsg = ( response && response.data && response.data.message ) ? response.data.message : window.uvpe_frontend.i18n.generic;
					showMessage( $message, errMsg, 'error' );
					resetButton( $button );
				}
			} )
			.fail( function () {
				showMessage( $message, window.uvpe_frontend.i18n.generic, 'error' );
				resetButton( $button );
			} );
	}

	function showMessage( $el, text, type ) {
		$el.removeClass( 'is-error is-success' );
		if ( type === 'error' ) {
			$el.addClass( 'is-error' );
		} else if ( type === 'success' ) {
			$el.addClass( 'is-success' );
		}
		$el.text( text );
	}

	function resetButton( $button ) {
		$button.prop( 'disabled', false );
		var original = $button.data( 'original-text' );
		if ( original ) {
			$button.text( original );
		}
	}
} )( jQuery );
