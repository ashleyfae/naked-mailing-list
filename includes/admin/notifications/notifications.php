<?php
/**
 * Notifications Admin Page
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
 * Render Notifications Page
 *
 * @since 1.0
 * @return void
 */
function nml_notifications_page() {
	$default_views  = nml_notification_views();
	$requested_view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'notifications';

	if ( array_key_exists( $requested_view, $default_views ) && function_exists( $default_views[ $requested_view ] ) ) {
		nml_render_notification_view( $requested_view, $default_views );
	} else {
		nml_notifications_list();
	}
}

/**
 * Register the views for notification management
 *
 * @see   nml_register_default_notification_views() - Actual views added here.
 *
 * @since 1.0
 * @return array Array of views and their callbacks.
 */
function nml_notification_views() {
	$views = array();

	return apply_filters( 'nml_notification_views', $views );
}

/**
 * Render table of notifications
 *
 * @since 1.0
 * @return void
 */
function nml_notifications_list() {

	include dirname( __FILE__ ) . '/class-notification-table.php';

	$notification_table = new NML_Notification_Table();
	$notification_table->prepare_items();
	?>
	<div class="wrap">
		<h1>
			<?php _e( 'Post Notifications', 'naked-mailing-list' ); ?>
			<a href="<?php echo esc_url( nml_get_admin_page_add_notification() ); ?>" class="page-title-action"><?php _e( 'Add New', 'naked-mailing-list' ); ?></a>
		</h1>

		<?php do_action( 'nml_notifications_table_top' ); ?>

		<form id="nml-notifications-filter" method="GET" action="<?php echo esc_url( nml_get_admin_page_notifications() ); ?>">
			<?php
			$notification_table->search_box( __( 'Search Notifications', 'naked-mailing-list' ), 'nml-notifications' );
			$notification_table->display();
			?>
			<input type="hidden" name="page" value="nml-notifications"/>
			<input type="hidden" name="view" value="notifications"/>
		</form>

		<?php do_action( 'nml_notifications_table_bottom' ); ?>
	</div>
	<?php

}

/**
 * Render Notification View
 *
 * @param string $view      The key of the view being requested.
 * @param array  $callbacks All the registered views and their callback functions.
 *
 * @since 1.0
 * @return void
 */
function nml_render_notification_view( $view, $callbacks ) {

	$notification_id = array_key_exists( 'ID', $_GET ) ? (int) $_GET['ID'] : 0;
	$notification    = new NML_Post_Notification( $notification_id );
	$render          = true;

	switch ( $view ) {
		case 'add' :
			$page_title = __( 'Add New Notification', 'naked-mailing-list' );
			break;

		case 'edit' :
			$page_title = __( 'Edit Notification', 'naked-mailing-list' );
			break;

		default :
			$page_title = __( 'Post Notifications', 'naked-mailing-list' );
			break;
	}

	if ( 'edit' == $view && empty( $notification->ID ) ) {
		nml_set_error( 'nml-invalid-notification', __( 'Invalid notification ID provided.', 'naked-mailing-list' ) );
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

		<div id="nml-notifications-page-wrapper">
			<form method="POST">
				<?php
				if ( $render ) {
					$callbacks[ $view ]( $notification );
				}
				?>
			</form>
		</div>
	</div>
	<?php

}

/**
 * View: add/edit notification.
 *
 * @param NML_Post_Notification $notification
 *
 * @since 1.0
 * @return void
 */
function nml_notifications_edit_view( $notification ) {

	if ( empty( $notification->post_type ) ) {
		$notification->post_type = 'post';
	}

	wp_nonce_field( 'nml_save_notification', 'nml_save_notification_nonce' );
	?>
	<input type="hidden" name="notification_id" value="<?php echo esc_attr( absint( $notification->ID ) ); ?>">
	<input type="hidden" name="nml_action" value="save_notification">

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div id="titlediv">
					<div id="titlewrap">
						<label id="title-prompt-text" for="title"><?php esc_html_e( 'Enter title here', 'naked-mailing-list' ); ?></label>
						<input type="text" name="notification_name" size="30" id="title" spellcheck="true" autocomplete="off">
					</div>
				</div>
				<div id="postbox-container-1" class="postbox-container">
					<div id="side-sortables" class="meta-box-sortables ui-sortables">
						<div id="submitdiv" class="postbox">
							<h2 class="hndle ui-sortable handle"><?php _e( 'Save Notification', 'naked-mailing-list' ); ?></h2>
							<div class="inside">
								<div class="submitbox" id="submitpost">
									<div id="minor-publishing">

										<div id="minor-publishing-actions">
											<p>
												<label for="nml_notification_activation"><?php esc_html_e( 'Status', 'naked-mailing-list' ); ?></label>
												<select id="nml_notification_activation" name="nml_notification_activation">
													<option value="1" <?php selected( $notification->active, 1 ); ?>><?php esc_html_e( 'Active', 'naked-mailing-list' ); ?></option>
													<option value="0" <?php selected( $notification->active, 0 ); ?>><?php esc_html_e( 'Inactive', 'naked-mailing-list' ); ?></option>
												</select>
											</p>

											<p>
												<label for="nml_post_type"><?php esc_html_e( 'Post Type', 'naked-mailing-list' ); ?></label>
												<select id="nml_post_type" name="nml_post_type">
													<option value="all" <?php selected( $notification->post_type, 'all' ); ?>><?php esc_html_e( 'All', 'naked-mailing-list' ); ?></option>
													<?php foreach ( apply_filters( 'nml_notification_post_types', get_post_types( array( 'public' => true ) ) ) as $post_type ) : ?>
														<option value="<?php echo esc_attr( $post_type ); ?>" <?php selected( $notification->post_type, $post_type ); ?>><?php echo esc_html( $post_type ); ?></option>
													<?php endforeach; ?>
												</select>
											</p>
										</div>

										<div id="misc-publishing-actions">

										</div>
									</div>

									<div id="major-publishing-actions">
										<div id="delete-action">
											<?php if ( $notification->ID ) : ?>
												<a href="<?php echo esc_url( nml_get_admin_page_delete_notification( $notification->ID ) ); ?>"><?php _e( 'Delete Notification', 'naked-mailing-list' ); ?></a>
											<?php endif; ?>
										</div>
										<div id="publishing-action">
											<input type="submit" id="nml-save-notification" name="save_notification" class="button button-primary button-large" value="<?php esc_attr_e( 'Save', 'naked-mailing-list' ); ?>">
										</div>
									</div>
								</div>
							</div>
						</div>

						<?php do_action( 'nml_edit_notification_after_save_box', $notification ); ?>

						<div id="nml-notification-lists" class="postbox">
							<h2 class="hndle ui-sortable handle"><?php _e( 'Lists', 'naked-mailing-list' ); ?></h2>
							<div class="inside">
								<?php do_action( 'nml_edit_notification_lists_box', $notification ); ?>
							</div>
						</div>

						<?php do_action( 'nml_edit_notification_after_lists_box', $notification ); ?>

						<div id="nml-notification-tags" class="postbox">
							<h2 class="hndle ui-sortable handle"><?php _e( 'Tags', 'naked-mailing-list' ); ?></h2>
							<div class="inside">
								<?php do_action( 'nml_edit_notification_tags_box', $notification ); ?>
							</div>
						</div>

						<?php do_action( 'nml_edit_notification_after_tags_box', $notification ); ?>

						<div id="nml-notification-template-tags" class="postbox">
							<h2 class="hndle ui-sortable handle"><?php _e( 'Template Tags', 'naked-mailing-list' ); ?></h2>
							<div class="inside">
								<?php do_action( 'nml_edit_notification_template_tags_box', $notification ); ?>
							</div>
						</div>

						<?php do_action( 'nml_edit_notification_after_template_tags_box', $notification ); ?>
					</div>
				</div>

				<div id="postbox-container-2" class="postbox-container">
					<?php do_action( 'nml_edit_notification_before_info_fields', $notification ); ?>

					<div class="postbox">
						<h2><?php _e( 'Notification Details', 'naked-mailing-list' ); ?></h2>
						<div class="inside">
							<?php do_action( 'nml_edit_notification_info_fields', $notification ); ?>
						</div>
					</div>

					<?php do_action( 'nml_edit_notification_after_info_fields', $notification ); ?>

					<div class="postbox">
						<h2><?php _e( 'Headers', 'naked-mailing-list' ); ?></h2>
						<div class="inside">
							<?php do_action( 'nml_edit_notification_headers_fields', $notification ); ?>
						</div>
					</div>

					<?php do_action( 'nml_edit_notification_after_headers_fields', $notification ); ?>
				</div>
			</div>
		</div>
	</div>
	<?php

}