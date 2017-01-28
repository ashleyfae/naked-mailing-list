<?php
/**
 * Subscriber Actions
 *
 * Mostly used for adding form fields to the add/edit subscriber page.
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
 * Field: Email address
 *
 * @param NML_Subscriber $subscriber
 *
 * @since 1.0
 * @return void
 */
function nml_subscriber_field_email( $subscriber ) {
	?>
	<div class="nml-field">
		<label for="nml_subscriber_email"><?php _e( 'Email address', 'naked-mailing-list' ); ?></label>
		<input type="email" id="nml_subscriber_email" class="regular-text" name="nml_subscriber_email" value="<?php echo esc_attr( $subscriber->email ); ?>" required>
	</div>
	<?php
}

add_action( 'nml_edit_subscriber_info_fields', 'nml_subscriber_field_email' );

/**
 * Field: First/last name
 *
 * @param NML_Subscriber $subscriber
 *
 * @since 1.0
 * @return void
 */
function nml_subscriber_field_name( $subscriber ) {
	?>
	<div class="nml-field">
		<div id="nml-subscriber-first-name">
			<label for="nml_subscriber_first_name"><?php _e( 'First name', 'naked-mailing-list' ); ?></label>
			<input type="text" id="nml_subscriber_first_name" class="regular-text" name="nml_subscriber_first_name" value="<?php echo esc_attr( $subscriber->first_name ); ?>">
		</div>

		<div id="nml-subscriber-last-name">
			<label for="nml_subscriber_last_name"><?php _e( 'Last name', 'naked-mailing-list' ); ?></label>
			<input type="text" id="nml_subscriber_last_name" class="regular-text" name="nml_subscriber_last_name" value="<?php echo esc_attr( $subscriber->last_name ); ?>">
		</div>
	</div>
	<?php
}

add_action( 'nml_edit_subscriber_info_fields', 'nml_subscriber_field_name' );

/**
 * Field: Notes
 *
 * @param NML_Subscriber $subscriber
 *
 * @since 1.0
 * @return void
 */
function nml_subscriber_field_notes( $subscriber ) {
	?>
	<div class="nml-field">
		<label for="nml_subscriber_notes"><?php _e( 'Notes', 'naked-mailing-list' ); ?></label>
		<textarea id="nml_subscriber_notes" class="large-text" name="nml_subscriber_notes" rows="10" cols="50"><?php echo esc_textarea( $subscriber->notes ); ?></textarea>
	</div>
	<?php
}

add_action( 'nml_edit_subscriber_info_fields', 'nml_subscriber_field_notes' );

/**
 * Box: Display subscriber activity
 *
 * @param NML_Subscriber $subscriber
 *
 * @since 1.0
 * @return void
 */
function nml_subscriber_activity_box( $subscriber ) {

	if ( empty( $subscriber->ID ) ) {
		return;
	}

	$activity = naked_mailing_list()->activity->get_activity( array( 'subscriber_id' => $subscriber->ID ) );

	if ( empty( $activity ) || ! is_array( $activity ) ) {
		return;
	}

	$name = ! empty( $subscriber->first_name ) ? $subscriber->first_name : __( 'This person', 'naked-mailing-list' );
	?>
	<div class="postbox">
		<h2><?php _e( 'Recent Activity', 'naked-mailing-list' ); ?></h2>
		<div class="inside">
			<ul>
				<?php foreach ( $activity as $entry ) : ?>
					<li class="nml-activity-entry nml-activity-entry-<?php echo sanitize_html_class( $entry->type ); ?>">
						<span class="nml-activity-date">
							<?php echo nml_format_mysql_date( $entry->date, nml_full_date_time_format() ); ?>
						</span>

						<span class="nml-activity-description">
							<?php
							switch ( $entry->type ) {

								case 'new_subscriber' :
									printf(
										_x( '%1$s subscribed via %2$s.', '%1$s is the name of the subscriber, %2$s is the method', 'naked-mailing-list' ),
										esc_html( $name ),
										$subscriber->get_referrer()
									);
									break;

								case 'subscriber_confirm' :
									printf(
										__( '%s confirmed their subscription.', 'naked-mailing-list' ),
										esc_html( $name )
									);

							}
							?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<?php

}

add_action( 'nml_edit_subscriber_after_info_fields', 'nml_subscriber_activity_box' );

/*
 * Below: Saving Functions
 */

/**
 * Save Subscriber
 *
 * Triggers after saving a subscriber.
 *
 * @since 1.0
 * @return void
 */
function nml_save_subscriber() {

	$nonce = isset( $_POST['nml_save_subscriber_nonce'] ) ? $_POST['nml_save_subscriber_nonce'] : false;

	if ( ! $nonce ) {
		return;
	}

	if ( ! wp_verify_nonce( $nonce, 'nml_save_subscriber' ) ) {
		wp_die( __( 'Failed security check.', 'naked-mailing-list' ) );
	}

	if ( ! current_user_can( 'edit_posts' ) ) { // @todo maybe change
		wp_die( __( 'You don\'t have permission to edit subscribers.', 'naked-mailing-list' ) );
	}

	$subscriber_id = $_POST['subscriber_id'];

	$sub_data = array(
		'ID' => absint( $subscriber_id )
	);

	// Email
	if ( isset( $_POST['nml_subscriber_email'] ) ) {
		$sub_data['email'] = $_POST['nml_subscriber_email'];
	}

	// First Name
	if ( isset( $_POST['nml_subscriber_first_name'] ) ) {
		$sub_data['first_name'] = $_POST['nml_subscriber_first_name'];
	}

	// Last Name
	if ( isset( $_POST['nml_subscriber_last_name'] ) ) {
		$sub_data['last_name'] = $_POST['nml_subscriber_last_name'];
	}

	// Status
	if ( isset( $_POST['nml_subscriber_status'] ) ) {
		$sub_data['status'] = $_POST['nml_subscriber_status'];
	}

	// Notes
	if ( isset( $_POST['nml_subscriber_notes'] ) ) {
		$sub_data['notes'] = $_POST['nml_subscriber_notes'];
	}

	// Omit IP address if manually adding the subscriber.
	if ( empty( $subscriber_id ) ) {
		$sub_data['ip'] = '';
	}

	$new_sub_id = nml_insert_subscriber( $sub_data );

	if ( ! $new_sub_id || is_wp_error( $new_sub_id ) ) {
		wp_die( __( 'An error occurred while inserting the subscriber.', 'naked-mailing-list' ) );
	}

	$edit_url = add_query_arg( array(
		'nml-message' => 'subscriber-updated'
	), nml_get_admin_page_edit_subscriber( absint( $new_sub_id ) ) );

	wp_safe_redirect( $edit_url );

	exit;

}

add_action( 'nml_save_subscriber', 'nml_save_subscriber' );