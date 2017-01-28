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
		<input type="email" id="nml_subscriber_email" name="nml_subscriber_email" value="<?php echo esc_attr( $subscriber->email ); ?>" required>
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
			<input type="text" id="nml_subscriber_first_name" name="nml_subscriber_first_name" value="<?php echo esc_attr( $subscriber->first_name ); ?>">
		</div>

		<div id="nml-subscriber-last-name">
			<label for="nml_subscriber_last_name"><?php _e( 'Last name', 'naked-mailing-list' ); ?></label>
			<input type="text" id="nml_subscriber_last_name" name="nml_subscriber_last_name" value="<?php echo esc_attr( $subscriber->last_name ); ?>">
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
		<textarea id="nml_subscriber_notes" name="nml_subscriber_notes"><?php echo esc_textarea( $subscriber->notes ); ?></textarea>
	</div>
	<?php
}

add_action( 'nml_edit_subscriber_info_fields', 'nml_subscriber_field_notes' );