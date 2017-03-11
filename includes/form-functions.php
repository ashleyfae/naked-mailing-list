<?php
/**
 * Form Functions
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
 * Subscribe form shortcode
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 *
 * @since 1.0
 * @return string
 */
function nml_subscribe_form( $atts, $content = '' ) {

	$atts = shortcode_atts( array(
		'first_name' => false,
		'last_name'  => false,
		'list'       => '',
		'tags'       => '',
		'form_name'  => '',
		'submit'     => __( 'Subscribe', 'naked-mailing-list' )
	), $atts, 'nml-subscribe' );

	$lists       = explode( ',', $atts['list'] );
	$list_string = implode( ',', array_map( 'absint', $lists ) );

	$tags       = explode( ',', $atts['tags'] );
	$tag_string = implode( ',', array_map( 'absint', $tags ) );

	ob_start();
	?>
	<form class="nml-subscribe-form" method="POST">
		<?php
		/**
		 * Include all form fields.
		 *
		 * @see nml_subscribe_form_first_name() - 10
		 * @see nml_subscribe_form_last_name() - 20
		 * @see nml_subscribe_form_email() - 30
		 * @see nml_subscribe_form_submit() - 100
		 */
		do_action( 'nml_subscribe_form_fields', $atts );

		/**
		 * Hidden fields.
		 */
		wp_referer_field();
		?>
		<input type="hidden" name="nml_list" value="<?php echo esc_attr( $list_string ); ?>">
		<input type="hidden" name="nml_tags" value="<?php echo esc_attr( $tag_string ); ?>">
		<input type="hidden" name="nml_form_name" value="<?php echo esc_attr( $atts['form_name'] ); ?>">

		<div class="nml-subscribe-response"></div>
	</form>
	<?php

	return apply_filters( 'nml_subscribe_form', ob_get_clean(), $atts );

}

add_shortcode( 'nml-subscribe', 'nml_subscribe_form' );

/**
 * Render Field: First Name
 *
 * @param array $atts Shortcode attributes.
 *
 * @since 1.0
 * @return void
 */
function nml_subscribe_form_first_name( $atts ) {

	if ( empty( $atts['first_name'] ) ) {
		return;
	}

	?>
	<div class="nml-form-field">
		<label for="nml_first_name"><?php _e( 'First name', 'naked-mailing-list' ); ?></label>
		<input type="text" id="nml_first_name" name="nml_first_name" placeholder="<?php echo esc_attr( apply_filters( 'nml_subscribe_form_first_name_placeholder', __( 'Enter your first name', 'naked-mailing-list' ) ) ); ?>">
	</div>
	<?php

}

add_action( 'nml_subscribe_form_fields', 'nml_subscribe_form_first_name', 10 );

/**
 * Render Field: Last Name
 *
 * @param array $atts Shortcode attributes.
 *
 * @since 1.0
 * @return void
 */
function nml_subscribe_form_last_name( $atts ) {

	if ( empty( $atts['last_name'] ) ) {
		return;
	}

	?>
	<div class="nml-form-field">
		<label for="nml_last_name"><?php _e( 'Last name', 'naked-mailing-list' ); ?></label>
		<input type="text" id="nml_last_name" name="nml_last_name" placeholder="<?php echo esc_attr( apply_filters( 'nml_subscribe_form_last_name_placeholder', __( 'Enter your last name', 'naked-mailing-list' ) ) ); ?>">
	</div>
	<?php

}

add_action( 'nml_subscribe_form_fields', 'nml_subscribe_form_last_name', 20 );

/**
 * Render Field: Email
 *
 * @param array $atts Shortcode attributes.
 *
 * @since 1.0
 * @return void
 */
function nml_subscribe_form_email( $atts ) {

	?>
	<div class="nml-form-field">
		<label for="nml_email_address"><?php _e( 'Email address', 'naked-mailing-list' ); ?></label>
		<input type="text" id="nml_email_address" name="nml_email_address" placeholder="<?php echo esc_attr( apply_filters( 'nml_subscribe_form_email_placeholder', __( 'Enter your email address', 'naked-mailing-list' ) ) ); ?>">
	</div>
	<?php

}

add_action( 'nml_subscribe_form_fields', 'nml_subscribe_form_email', 30 );

/**
 * Render Field: Submit
 *
 * @param array $atts Shortcode attributes.
 *
 * @since 1.0
 * @return void
 */
function nml_subscribe_form_submit( $atts ) {

	?>
	<div class="nml-submit-field">
		<button type="submit" class="button"><?php echo $atts['submit']; ?></button>
	</div>
	<?php

}

add_action( 'nml_subscribe_form_fields', 'nml_subscribe_form_submit', 100 );

/**
 * Process signup via ajax
 *
 * @since 1.0
 * @return void
 */
function nml_process_signup() {

	$email = $_POST['email'];
	$lists = ! empty( $_POST['list'] ) ? array_map( 'absint', explode( ',', $_POST['list'] ) ) : false;
	$tags  = ! empty( $_POST['tags'] ) ? array_map( 'trim', explode( ',', $_POST['tags'] ) ) : false;

	$data = array(
		'ip' => nml_get_ip()
	);

	if ( ! empty( $email ) && is_email( $email ) ) {
		$data['email'] = sanitize_email( $email );
	} else {
		nml_set_error( 'email-required', __( 'You must enter a valid email address.', 'naked-mailing-list' ) );
	}

	$fields = array( 'first_name', 'last_name', 'referer', 'form_name' );

	foreach ( $fields as $field ) {
		if ( isset( $_POST[ $field ] ) && ! empty( $_POST[ $field ] ) ) {
			$data[ $field ] = sanitize_text_field( $_POST[ $field ] );
		}
	}

	/*
	 * Send back error messages if we have them.
	 */
	if ( nml_get_errors() ) {
		wp_send_json_error( nml_print_errors( true ) );
	}

	if ( naked_mailing_list()->subscribers->exists( $email ) ) {

		/*
		 * Subscriber already exists - let's update them.
		 */
		$object     = naked_mailing_list()->subscribers->get_subscriber_by( 'email', $email );
		$subscriber = new NML_Subscriber( $object->ID );
		$subscriber->update( $data );

		if ( $lists ) {
			foreach ( $lists as $list_id ) {
				$subscriber->add_to_list( $list_id );
			}
		}

		if ( $tags ) {
			foreach ( $tags as $tag ) {
				$subscriber->tag( $tag );
			}
		}

		$message = __( 'You\'ve successfully been added to the list!', 'naked-mailing-list' );

	} else {

		/*
		 * Add a brand new subscriber.
		 */
		$data['status'] = 'pending';
		if ( $lists ) {
			$data['lists'] = $lists;
		}
		if ( $tags ) {
			$data['tags'] = $tags;
		}

		$subscriber = new NML_Subscriber();
		$subscriber->create( $data );

		$message = __( 'Almost there! Check your email to confirm your subscription.', 'naked-mailing-list' );
	}

	wp_send_json_success( $message );

	exit;

}

add_action( 'wp_ajax_nml_process_signup', 'nml_process_signup' );
add_action( 'wp_ajax_nopriv_nml_process_signup', 'nml_process_signup' );