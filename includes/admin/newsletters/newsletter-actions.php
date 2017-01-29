<?php
/**
 * Newsletter Actions
 *
 * Used for adding fields to the add/edit newsletter page and processing actions.
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
 * Field: Subject
 *
 * @param NML_Newsletter $newsletter
 *
 * @since 1.0
 * @return void
 */
function nml_newsletter_field_subject( $newsletter ) {
	?>
	<div class="nml-field">
		<label for="nml_newsletter_subject"><?php _e( 'Subject', 'naked-mailing-list' ); ?></label>
		<input type="text" id="nml_newsletter_subject" class="regular-text" name="nml_newsletter_subject" value="<?php echo esc_attr( $newsletter->subject ); ?>" required>
	</div>
	<?php
}

add_action( 'nml_edit_newsletter_info_fields', 'nml_newsletter_field_subject' );

/**
 * Field: Body
 *
 * @param NML_Newsletter $newsletter
 *
 * @since 1.0
 * @return void
 */
function nml_newsletter_field_body( $newsletter ) {
	?>
	<div class="nml-field">
		<label for="nml_newsletter_body"><?php _e( 'Body', 'naked-mailing-list' ); ?></label>
		<?php
		wp_editor( $newsletter->body, 'nml_newsletter_body', array(
			'textarea_name' => 'nml_newsletter_body',
			'editor_class'  => 'large-text'
		) );
		?>
	</div>
	<?php
}

add_action( 'nml_edit_newsletter_info_fields', 'nml_newsletter_field_body' );

/**
 * Field: From Name
 *
 * @param NML_Newsletter $newsletter
 *
 * @since 1.0
 * @return void
 */
function nml_newsletter_field_from_name( $newsletter ) {
	?>
	<div class="nml-field">
		<label for="nml_newsletter_from_name"><?php _e( 'From Name', 'naked-mailing-list' ); ?></label>
		<input type="text" id="nml_newsletter_from_name" class="regular-text" name="nml_newsletter_from_name" value="<?php echo esc_attr( $newsletter->from_name ); ?>" required>
	</div>
	<?php
}

add_action( 'nml_edit_newsletter_headers_fields', 'nml_newsletter_field_from_name' );

/**
 * Field: From Address
 *
 * @param NML_Newsletter $newsletter
 *
 * @since 1.0
 * @return void
 */
function nml_newsletter_field_from_address( $newsletter ) {
	?>
	<div class="nml-field">
		<label for="nml_newsletter_from_address"><?php _e( 'From Address', 'naked-mailing-list' ); ?></label>
		<input type="email" id="nml_newsletter_from_address" class="regular-text" name="nml_newsletter_from_address" value="<?php echo esc_attr( $newsletter->from_address ); ?>" required>
	</div>
	<?php
}

add_action( 'nml_edit_newsletter_headers_fields', 'nml_newsletter_field_from_address' );

/**
 * Field: Reply-To Name
 *
 * @param NML_Newsletter $newsletter
 *
 * @since 1.0
 * @return void
 */
function nml_newsletter_field_reply_to_name( $newsletter ) {
	?>
	<div class="nml-field">
		<label for="nml_newsletter_reply_to_name"><?php _e( 'Reply-To Name', 'naked-mailing-list' ); ?></label>
		<input type="text" id="nml_newsletter_reply_to_name" class="regular-text" name="nml_newsletter_reply_to_name" value="<?php echo esc_attr( $newsletter->reply_to_name ); ?>" required>
	</div>
	<?php
}

add_action( 'nml_edit_newsletter_headers_fields', 'nml_newsletter_field_reply_to_name' );

/**
 * Field: Reply-To Address
 *
 * @param NML_Newsletter $newsletter
 *
 * @since 1.0
 * @return void
 */
function nml_newsletter_field_reply_to_address( $newsletter ) {
	?>
	<div class="nml-field">
		<label for="nml_newsletter_reply_to_address"><?php _e( 'Reply-To Address', 'naked-mailing-list' ); ?></label>
		<input type="email" id="nml_newsletter_reply_to_address" class="regular-text" name="nml_newsletter_reply_to_address" value="<?php echo esc_attr( $newsletter->reply_to_address ); ?>" required>
	</div>
	<?php
}

add_action( 'nml_edit_newsletter_headers_fields', 'nml_newsletter_field_reply_to_address' );