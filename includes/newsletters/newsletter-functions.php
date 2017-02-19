<?php
/**
 * Newsletter Functions
 *
 * @package   naked-mailing-list
 * @copyright Copyright (c) 2017, Ashley Gibson
 * @license   GPL2+
 * @since     1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get array of available newsletter statuses
 *
 * @since 1.0
 * @return array
 */
function nml_get_newsletter_statuses() {
	$statuses = array(
		'draft'     => esc_html__( 'Draft', 'naked-mailing-list' ),
		'scheduled' => esc_html__( 'Scheduled', 'naked-mailing-list' ),
		'sending'   => esc_html__( 'Sending', 'naked-mailing-list' ),
		'sent'      => esc_html__( 'Sent', 'naked-mailing-list' )
	);

	return apply_filters( 'nml_newsletter_statuses', $statuses );
}

/**
 * Get newsletters
 *
 * @param array $args
 *
 * @since 1.0
 * @return array|false
 */
function nml_get_newsletters( $args = array() ) {
	return naked_mailing_list()->newsletters->get_newsletters( $args );
}

/**
 * @param array $newsletter_data Array of newsletter data. Arguments include:
 *                               `ID` - To update an existing newsletter (optional).
 *                               `status` - Newsletter status. @see nml_get_newsletter_statuses()
 *                               `subject` - Newsletter subject.
 *                               `body` - Content of the newsletter.
 *                               `from_address` - Email to send the newsletter from.
 *                               `from_name` - Name to send the newsletter from.
 *                               `reply_to_address` - Email to reply to.
 *                               `reply_to_name` - Reply-to name.
 *                               `created_date` - Date the newsletter was created (optional). Omit to use current time.
 *                               `updated_date` - Date the newsletter was last updated (optional). Omit to use current
 *                               time.
 *                               `sent_date` - Date the newsletter was sent (optional).
 *                               `lists` - Array of list IDs.
 *                               `tags` - Array of tag IDs.
 *
 * @since 1.0
 * @return int|WP_Error ID of the newsletter inserted or updated, or WP_Error on failure.
 */
function nml_insert_newsletter( $newsletter_data ) {

	$newsletter_db_data = array();

	$newsletter_db_data['status']           = ( array_key_exists( 'status', $newsletter_data ) && array_key_exists( $newsletter_data['status'], nml_get_newsletter_statuses() ) ) ? sanitize_text_field( $newsletter_data['status'] ) : 'draft';
	$newsletter_db_data['subject']          = array_key_exists( 'subject', $newsletter_data ) ? sanitize_text_field( $newsletter_data['subject'] ) : '';
	$newsletter_db_data['body']             = array_key_exists( 'body', $newsletter_data ) ? sanitize_text_field( $newsletter_data['body'] ) : '';
	$newsletter_db_data['from_address']     = array_key_exists( 'from_address', $newsletter_data ) ? sanitize_text_field( $newsletter_data['from_address'] ) : '';
	$newsletter_db_data['from_name']        = array_key_exists( 'from_name', $newsletter_data ) ? sanitize_text_field( $newsletter_data['from_name'] ) : '';
	$newsletter_db_data['reply_to_address'] = array_key_exists( 'reply_to_address', $newsletter_data ) ? sanitize_text_field( $newsletter_data['reply_to_address'] ) : '';
	$newsletter_db_data['reply_to_name']    = array_key_exists( 'reply_to_name', $newsletter_data ) ? sanitize_text_field( $newsletter_data['reply_to_name'] ) : '';
	$newsletter_db_data['updated_date']     = array_key_exists( 'updated_date', $newsletter_data ) ? sanitize_text_field( get_gmt_from_date( $newsletter_data['updated_date'] ) ) : gmdate( 'Y-m-d H:i:s' );

	if ( array_key_exists( 'created_date', $newsletter_data ) ) {
		$newsletter_db_data['created_date'] = sanitize_text_field( get_gmt_from_date( $newsletter_data['created_date'] ) );
	}

	if ( array_key_exists( 'sent_date', $newsletter_data ) ) {
		$newsletter_db_data['sent_date'] = sanitize_text_field( get_gmt_from_date( $newsletter_data['sent_date'] ) );
	}

	if ( array_key_exists( 'ID', $newsletter_data ) ) {
		$newsletter_db_data['ID'] = absint( $newsletter_data['ID'] );
	}

	if ( array_key_exists( 'ID', $newsletter_db_data ) ) {

		// Update existing newsletter.
		$newsletter = new NML_Newsletter( $newsletter_db_data['ID'] );
		$newsletter->update( $newsletter_db_data );
		$newsletter_id = $newsletter->ID;

	} else {

		// Insert new newsletter.
		$newsletter = new NML_Newsletter();
		$newsletter->create( $newsletter_db_data );
		$newsletter_id = $newsletter->ID;

	}

	if ( empty( $newsletter_id ) ) {
		return new WP_Error( 'error-inserting-newsletter', __( 'Error inserting newsletter into the database.', 'naked-mailing-list' ) );
	}

	/*
	 * Set lists
	 */

	if ( array_key_exists( 'lists', $newsletter_data ) ) {
		nml_set_object_lists( 'newsletter', $newsletter_id, $newsletter_data['lists'], 'list', false );
	}
	if ( array_key_exists( 'tags', $newsletter_data ) ) {
		nml_set_object_lists( 'newsletter', $newsletter_id, $newsletter_data['tags'], 'tag', false );
	}

	/*
	 * Return the newsletter ID.
	 */

	return $newsletter_id;

}

/**
 * Send newsletter
 *
 * Initiates the sending process by creating a queue entry.
 *
 * @param int $newsletter_id
 *
 * @since 1.0
 * @return int|false ID of the queue entry that was inserted, or false on failure.
 */
function nml_send_newsletter( $newsletter_id ) {

	// Make sure this newsletter exists.
	if ( ! naked_mailing_list()->newsletters->exists( $newsletter_id ) ) {
		return false;
	}

	$result = naked_mailing_list()->queue->add( array(
		'newsletter_id' => absint( $newsletter_id )
	) );

	return $result;

}

/**
 * Trigger newsletter send when the status is changed to "sending".
 *
 * @param string         $new_status
 * @param string         $old_status
 * @param int            $newsletter_id
 * @param NML_Newsletter $newsletter
 *
 * @since 1.0
 * @return void
 */
function nml_send_newsletter_on_status_change( $new_status, $old_status, $newsletter_id, $newsletter ) {

	if ( 'sending' == $new_status && 'sending' != $old_status ) {
		nml_send_newsletter( $newsletter_id );
	}

}

add_action( 'nml_newsletter_transition_status', 'nml_send_newsletter_on_status_change', 10, 4 );