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
		'referrer'   => '',
		'form_name'  => ''
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
		 */
		do_action( 'nml_subscribe_form', $atts );

		/**
		 * Hidden fields.
		 */
		?>
		<input type="hidden" name="nml_list" value="<?php echo esc_attr( $list_string ); ?>">
		<input type="hidden" name="nml_tags" value="<?php echo esc_attr( $tag_string ); ?>">
		<input type="hidden" name="nml_form_name" value="<?php echo esc_attr( $atts['form_name'] ); ?>">
	</form>
	<?php

	return apply_filters( 'nml_subscribe_form', ob_get_clean(), $atts );

}

add_shortcode( 'nml-subscribe', 'nml_subscribe_form' );