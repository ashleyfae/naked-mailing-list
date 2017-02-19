<?php
/**
 * Newsletters Admin Page
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
 * Render Newsletters Page
 *
 * @since 1.0
 * @return void
 */
function nml_newsletters_page() {
	$default_views  = nml_newsletter_views();
	$requested_view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'newsletters';

	if ( array_key_exists( $requested_view, $default_views ) && function_exists( $default_views[ $requested_view ] ) ) {
		nml_render_newsletter_view( $requested_view, $default_views );
	} else {
		nml_newsletters_list();
	}
}

/**
 * Register the views for newsletter management
 *
 * @see   nml_register_default_newsletter_views() - Actual views added here.
 *
 * @since 1.0
 * @return array Array of views and their callbacks.
 */
function nml_newsletter_views() {
	$views = array();

	return apply_filters( 'nml_newsletter_views', $views );
}

/**
 * Render table of newsletters
 *
 * @since 1.0
 * @return void
 */
function nml_newsletters_list() {

	include dirname( __FILE__ ) . '/class-newsletter-table.php';

	$newsletter_table = new NML_Newsletter_Table();
	$newsletter_table->prepare_items();
	?>
	<div class="wrap">
		<h1>
			<?php _e( 'Newsletters', 'naked-mailing-list' ); ?>
			<a href="<?php echo esc_url( nml_get_admin_page_add_newsletter() ); ?>" class="page-title-action"><?php _e( 'Add New', 'naked-mailing-list' ); ?></a>
		</h1>

		<?php do_action( 'nml_newsletters_table_top' ); ?>

		<form id="nml-newsletters-filter" method="GET" action="<?php echo esc_url( nml_get_admin_page_newsletters() ); ?>">
			<?php
			$newsletter_table->search_box( __( 'Search Newsletters', 'naked-mailing-list' ), 'nml-newsletters' );
			$newsletter_table->display();
			?>
			<input type="hidden" name="page" value="nml-newsletters"/>
			<input type="hidden" name="view" value="newsletters"/>
		</form>

		<?php do_action( 'nml_newsletters_table_bottom' ); ?>
	</div>
	<?php

}

/**
 * Render Newsletter View
 *
 * @param string $view      The key of the view being requested.
 * @param array  $callbacks All the registered views and their callback functions.
 *
 * @since 1.0
 * @return void
 */
function nml_render_newsletter_view( $view, $callbacks ) {

	$newsletter_id = array_key_exists( 'ID', $_GET ) ? (int) $_GET['ID'] : 0;
	$newsletter    = new NML_Newsletter( $newsletter_id );
	$render        = true;

	switch ( $view ) {
		case 'add' :
			$page_title = __( 'Add New Newsletter', 'naked-mailing-list' );
			break;

		case 'edit' :
			$page_title = __( 'Edit Newsletter', 'naked-mailing-list' );
			break;

		default :
			$page_title = __( 'Newsletters', 'naked-mailing-list' );
			break;
	}

	if ( 'edit' == $view && empty( $newsletter->ID ) ) {
		nml_set_error( 'nml-invalid-newsletter', __( 'Invalid newsletter ID provided.', 'naked-mailing-list' ) );
		$render = false;
	}

	?>
	<div class="wrap">
		<h1><?php echo $page_title; ?></h1>
		<?php if ( nml_get_errors() ) : ?>
			<div class="error settings-error">
				<?php nml_print_errors(); ?>
			</div>
		<?php endif; ?>

		<div id="nml-newsletters-page-wrapper">
			<form method="POST">
				<?php
				if ( $render ) {
					$callbacks[ $view ]( $newsletter );
				}
				?>
			</form>
		</div>
	</div>
	<?php

}

/**
 * View: add/edit newsletter.
 *
 * @param NML_Newsletter $newsletter
 *
 * @since 1.0
 * @return void
 */
function nml_newsletters_edit_view( $newsletter ) {

	wp_nonce_field( 'nml_save_newsletter', 'nml_save_newsletter_nonce' );
	?>
	<input type="hidden" name="newsletter_id" value="<?php echo esc_attr( absint( $newsletter->ID ) ); ?>">
	<input type="hidden" name="nml_action" value="save_newsletter">

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div id="postbox-container-1" class="postbox-container">
					<div id="side-sortables" class="meta-box-sortables ui-sortables">
						<div id="submitdiv" class="postbox">
							<h2 class="hndle ui-sortable handle"><?php _e( 'Save Newsletter', 'naked-mailing-list' ); ?></h2>
							<div class="inside">
								<div class="submitbox" id="submitpost">
									<div id="minor-publishing">

										<div id="minor-publishing-actions">
											<div id="save-action">
												<input type="submit" name="save" id="save-post" value="<?php esc_attr_e( 'Save Draft', 'naked-mailing-list' ); ?>" class="button">
											</div>
											<div id="preview-action">
												<a class="preview button" href="" target="_blank" id="post-preview"><?php esc_html_e( 'Preview', 'naked-mailing-list' ); ?></a>
											</div>
											<div class="clear"></div>
										</div>

										<div id="misc-publishing-actions">

										</div>
									</div>

									<div id="major-publishing-actions">
										<div id="delete-action">
											<?php if ( $newsletter->ID ) : ?>
												<a href="<?php echo esc_url( nml_get_admin_page_delete_newsletter( $newsletter->ID ) ); ?>"><?php _e( 'Delete Newsletter', 'naked-mailing-list' ); ?></a>
											<?php endif; ?>
										</div>
										<div id="publishing-action">
											<input type="submit" id="nml-save-newsletter" name="save_newsletter" class="button button-primary button-large" value="<?php esc_attr_e( 'Send Now', 'naked-mailing-list' ); ?>">
										</div>
									</div>
								</div>
							</div>
						</div>

						<?php do_action( 'nml_edit_newsletter_after_save_box', $newsletter ); ?>

						<div id="nml-newsletter-lists" class="postbox">
							<h2 class="hndle ui-sortable handle"><?php _e( 'Lists', 'naked-mailing-list' ); ?></h2>
							<div class="inside">
								<?php do_action( 'nml_edit_newsletter_lists_box', $newsletter ); ?>
							</div>
						</div>

						<?php do_action( 'nml_edit_newsletter_after_lists_box', $newsletter ); ?>

						<div id="nml-newsletter-tags" class="postbox">
							<h2 class="hndle ui-sortable handle"><?php _e( 'Tags', 'naked-mailing-list' ); ?></h2>
							<div class="inside">
								<?php do_action( 'nml_edit_newsletter_tags_box', $newsletter ); ?>
							</div>
						</div>

						<?php do_action( 'nml_edit_newsletter_after_tags_box', $newsletter ); ?>

						<div id="nml-newsletter-template-tags" class="postbox">
							<h2 class="hndle ui-sortable handle"><?php _e( 'Template Tags', 'naked-mailing-list' ); ?></h2>
							<div class="inside">
								<?php do_action( 'nml_edit_newsletter_template_tags_box', $newsletter ); ?>
							</div>
						</div>

						<?php do_action( 'nml_edit_newsletter_after_template_tags_box', $newsletter ); ?>
					</div>
				</div>

				<div id="postbox-container-2" class="postbox-container">
					<?php do_action( 'nml_edit_newsletter_before_info_fields', $newsletter ); ?>

					<div class="postbox">
						<h2><?php _e( 'Newsletter Details', 'naked-mailing-list' ); ?></h2>
						<div class="inside">
							<?php do_action( 'nml_edit_newsletter_info_fields', $newsletter ); ?>
						</div>
					</div>

					<?php do_action( 'nml_edit_newsletter_after_info_fields', $newsletter ); ?>

					<div class="postbox">
						<h2><?php _e( 'Headers', 'naked-mailing-list' ); ?></h2>
						<div class="inside">
							<?php do_action( 'nml_edit_newsletter_headers_fields', $newsletter ); ?>
						</div>
					</div>

					<?php do_action( 'nml_edit_newsletter_after_headers_fields', $newsletter ); ?>
				</div>
			</div>
		</div>
	</div>
	<?php

}