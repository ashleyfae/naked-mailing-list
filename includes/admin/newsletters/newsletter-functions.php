<?php
/**
 * Newsletter Functions
 *
 * @package   naked-mailing-list
 * @copyright Copyright (c) 2017, Ashley Gibson
 * @license   GPL2+
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register default newsletter views.
 *
 * @param array $views
 *
 * @since 1.0
 * @return array
 */
function nml_register_default_newsletter_views( $views ) {

	$default_views = array(
		'add'  => 'nml_newsletters_edit_view',
		'edit' => 'nml_newsletters_edit_view'
	);

	return array_merge( $views, $default_views );

}

add_filter( 'nml_newsletter_views', 'nml_register_default_newsletter_views', 1 );