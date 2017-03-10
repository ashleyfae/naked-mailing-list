<?php
/**
 * Lists Admin Page
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
 * Render Lists Page
 *
 * @since 1.0
 * @return void
 */
function nml_lists_page() {
	$default_views  = nml_list_views();
	$requested_view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'lists';

	if ( array_key_exists( $requested_view, $default_views ) && function_exists( $default_views[ $requested_view ] ) ) {
		nml_render_list_view( $requested_view, $default_views );
	} else {
		nml_lists_table();
	}
}

/**
 * Register the views for list management
 *
 * @see   nml_register_default_list_views() - Actual views added here.
 *
 * @since 1.0
 * @return array Array of views and their callbacks.
 */
function nml_list_views() {
	$views = array();

	return apply_filters( 'nml_list_views', $views );
}

/**
 * Render table of lists
 *
 * @since 1.0
 * @return void
 */
function nml_lists_table() {

	include dirname( __FILE__ ) . '/class-list-table.php';

	$list_table = new NML_List_Table();
	$list_table->prepare_items();

	$list              = new stdClass();
	$list->name        = '';
	$list->description = '';
	$list->type        = 'list';
	$list->count       = 0;
	?>
	<div class="wrap">
		<h1><?php _e( 'Lists', 'naked-mailing-list' ); ?></h1>

		<div id="col-container" class="wp-clearfix">

			<div id="col-left">
				<div class="col-wrap">
					<div class="form-wrap">
						<h2><?php _e( 'Add New List', 'naked-mailing-list' ); ?></h2>
						<form id="nml-add-list" method="POST" action="<?php echo esc_url( nml_get_admin_page_lists() ); ?>">
							<?php
							/*
							 * Form fields added here.
							 */
							do_action( 'nml_edit_list_fields', $list );

							wp_nonce_field( 'nml_save_list', 'nml_save_list_nonce' );
							?>
							<input type="hidden" name="list_id" value="0">
							<input type="hidden" name="nml_action" value="save_list">
							<p class="submit">
								<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Add New Term', 'naked-mailing-list' ); ?>">
							</p>
						</form>
					</div>
				</div>
			</div>

			<div id="col-right">
				<div class="col-wrap">

					<?php do_action( 'nml_lists_table_top' ); ?>

					<form id="nml-lists-filter" method="GET" action="<?php echo esc_url( nml_get_admin_page_lists() ); ?>">
						<?php
						$list_table->search_box( __( 'Search Lists', 'naked-mailing-list' ), 'nml-lists' );
						$list_table->display();
						?>
						<input type="hidden" name="page" value="nml-lists"/>
						<input type="hidden" name="view" value="lists"/>
					</form>

					<?php do_action( 'nml_lists_table_bottom' ); ?>

				</div>
			</div>

		</div>
	</div>
	<?php

}

/**
 * Render List View
 *
 * @param string $view      The key of the view being requested.
 * @param array  $callbacks All the registered views and their callback functions.
 *
 * @since 1.0
 * @return void
 */
function nml_render_list_view( $view, $callbacks ) {

	$list_id = array_key_exists( 'ID', $_GET ) ? (int) $_GET['ID'] : 0;
	$list    = naked_mailing_list()->lists->get( $list_id );
	$render  = true;

	switch ( $view ) {
		case 'add' :
			$page_title = __( 'Add New List', 'naked-mailing-list' );
			break;

		case 'edit' :
			$page_title = __( 'Edit List', 'naked-mailing-list' );
			break;

		default :
			$page_title = __( 'Lists', 'naked-mailing-list' );
			break;
	}

	if ( 'edit' == $view && ! is_object( $list ) ) {
		nml_set_error( 'nml-invalid-list', __( 'Invalid list ID provided.', 'naked-mailing-list' ) );
		$render = false;
	}

	if ( ! is_object( $list ) ) {
		$list              = new stdClass();
		$list->name        = '';
		$list->description = '';
		$list->type        = 'list';
		$list->count       = 0;
	}

	?>
	<div class="wrap">
		<h1><?php echo $page_title; ?></h1>
		<?php if ( nml_get_errors() ) : ?>
			<div class="error settings-error">
				<?php nml_print_errors(); ?>
			</div>
		<?php endif; ?>

		<div id="nml-lists-page-wrapper">
			<form method="POST">
				<?php
				if ( $render ) {
					$callbacks[ $view ]( $list );
				}
				?>
			</form>
		</div>
	</div>
	<?php

}

/**
 * View: add/edit list.
 *
 * @param object $list Row from the list table.
 *
 * @since 1.0
 * @return void
 */
function nml_lists_edit_view( $list ) {

	wp_nonce_field( 'nml_save_list', 'nml_save_list_nonce' );
	?>
	<input type="hidden" name="list_id" value="<?php echo esc_attr( absint( $list->ID ) ); ?>">
	<input type="hidden" name="nml_action" value="save_list">

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div id="postbox-container-1" class="postbox-container">
					<div id="side-sortables" class="meta-box-sortables ui-sortables">
						<div id="submitdiv" class="postbox">
							<h2 class="hndle ui-sortable handle"><?php _e( 'Save List', 'naked-mailing-list' ); ?></h2>
							<div class="inside">
								<div class="submitbox" id="submitpost">
									<div id="major-publishing-actions">
										<div id="delete-action">
											<?php if ( $list->ID ) : ?>
												<a href="<?php echo esc_url( nml_get_admin_page_delete_list( $list->ID ) ); ?>"><?php _e( 'Delete List', 'naked-mailing-list' ); ?></a>
											<?php endif; ?>
										</div>
										<div id="publishing-action">
											<input type="submit" id="nml-save-list" name="save_list" class="button button-primary button-large" value="<?php esc_attr_e( 'Send Now', 'naked-mailing-list' ); ?>">
										</div>
									</div>
								</div>
							</div>
						</div>

						<?php do_action( 'nml_edit_list_after_save_box', $list ); ?>
					</div>
				</div>

				<div id="postbox-container-2" class="postbox-container">
					<?php do_action( 'nml_edit_list_before_fields', $list ); ?>

					<div class="postbox">
						<h2><?php _e( 'List Details', 'naked-mailing-list' ); ?></h2>
						<div class="inside">
							<?php do_action( 'nml_edit_list_fields', $list ); ?>
						</div>
					</div>

					<?php do_action( 'nml_edit_list_after_fields', $list ); ?>
				</div>
			</div>
		</div>
	</div>
	<?php

}