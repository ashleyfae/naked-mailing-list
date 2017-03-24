<?php
/**
 * Post Actions
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
 * Add "Preview Email" link to post actions.
 *
 * @param array   $actions Array of row actions.
 * @param WP_Post $post    Current post object.
 *
 * @since 1.0
 * @return array
 */
function nml_post_row_actions( $actions, $post ) {

	/**
	 * Whether or not the row actions should be added. This can be used to hide
	 * the actions on certain post types, etc.
	 *
	 * @param bool    $show    Whether or not to show the actions.
	 * @param WP_Post $post    Current post object.
	 * @param array   $actions Existing row actions.
	 *
	 * @since 1.0
	 */
	if ( ! apply_filters( 'nml_post_row_actions', true, $post, $actions ) ) {
		return $actions;
	}

	$url                      = add_query_arg( 'preview_email', 'true', get_permalink( $post ) );
	$actions['preview_email'] = sprintf( __( '<a href="%s" target="_blank">Preview Email</a>', 'naked-mailing-list' ), esc_url( $url ) );

	return $actions;

}

add_filter( 'post_row_actions', 'nml_post_row_actions', 10, 2 );