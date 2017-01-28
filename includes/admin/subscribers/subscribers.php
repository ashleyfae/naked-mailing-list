<?php
/**
 * Subscribers
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
 * Render Subscribers Page
 *
 * @since 1.0
 * @return void
 */
function nml_subscribers_page() {
	$default_views  = nml_subscriber_views();
	$requested_view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'subscribers';

	if ( array_key_exists( $requested_view, $default_views ) && function_exists( $default_views[ $requested_view ] ) ) {
		nml_render_subscriber_view( $requested_view, $default_views );
	} else {
		nml_subscribers_list();
	}
}

/**
 * Register the views for subscriber management
 *
 * @see   nml_register_default_subscriber_views() - Actual views added here.
 *
 * @since 1.0
 * @return array Array of views and their callbacks.
 */
function nml_subscriber_views() {
	$views = array();

	return apply_filters( 'nml_subscriber_views', $views );
}

/**
 * Render table of subscribers
 *
 * @since 1.0
 * @return void
 */
function nml_subscribers_list() {

	include dirname( __FILE__ ) . '/class-subscriber-table.php';

	$subscriber_table = new NML_Subscriber_Table();
	$subscriber_table->prepare_items();
	?>
	<div class="wrap">
		<h1>
			<?php _e( 'Subscribers', 'naked-mailing-list' ); ?>
			<a href="<?php echo esc_url( nml_get_admin_page_add_subscriber() ); ?>" class="page-title-action"><?php _e( 'Add New', 'naked-mailing-list' ); ?></a>
		</h1>

		<?php do_action( 'nml_subscribers_table_top' ); ?>

		<form id="nml-subscribers-filter" method="GET" action="<?php echo admin_url( 'edit.php?post_type=download&page=edd-customers' ); ?>">
			<?php
			$subscriber_table->search_box( __( 'Search Subscribers', 'naked-mailing-list' ), 'nml-subscribers' );
			$subscriber_table->display();
			?>
			<input type="hidden" name="page" value="nml-newsletters"/>
			<input type="hidden" name="view" value="subscribers"/>
		</form>

		<?php do_action( 'nml_subscribers_table_bottom' ); ?>
	</div>
	<?php

}

/**
 * Render Subscriber View
 *
 * @param string $view      The key of the view being requested.
 * @param array  $callbacks All the registered views and their callback functions.
 *
 * @since 1.0
 * @return void
 */
function nml_render_subscriber_view( $view, $callbacks ) {

	$subscriber_id = array_key_exists( 'ID', $_GET ) ? (int) $_GET['ID'] : 0;
	$subscriber    = new NML_Subscriber( $subscriber_id );
	$render        = true;

	switch ( $view ) {
		case 'add' :
			$page_title = __( 'Add New Subscriber', 'naked-mailing-list' );
			break;

		case 'edit' :
			$page_title = __( 'Edit Subscriber', 'naked-mailing-list' );
			break;

		default :
			$page_title = __( 'Subscribers', 'naked-mailing-list' );
			break;
	}

	if ( 'edit' == $view && empty( $subscriber->ID ) ) {
		nml_set_error( 'nml-invalid-subscriber', __( 'Invalid subscriber ID provided.', 'naked-mailing-list' ) );
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

		<div id="nml-subscribers-page-wrapper">
			<form method="POST">
				<?php
				if ( $render ) {
					$callbacks[ $view ]( $subscriber );
				}
				?>
			</form>
		</div>
	</div>
	<?php

}

/**
 * View: add/edit subscriber.
 *
 * @param NML_Subscriber $subscriber
 *
 * @since 1.0
 * @return void
 */
function nml_subscribers_edit_view( $subscriber ) {

	wp_nonce_field( 'nml_save_subscriber', 'nml_save_subscriber_nonce' );
	?>
	<input type="hidden" name="subscriber_id" value="<?php echo esc_attr( absint( $subscriber->ID ) ); ?>">
	<input type="hidden" name="nml_action" value="save_subscriber">

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div id="postbox-container-1" class="postbox-container">
					<div id="side-sortables" class="meta-box-sortables ui-sortables">
						<div id="submitdiv" class="postbox">
							<h2 class="hndle ui-sortable handle"><?php _e( 'Save Subscriber', 'naked-mailing-list' ); ?></h2>
							<div class="inside">
								<div class="submitbox" id="submitpost">
									<div id="minor-publishing">
										<div id="misc-publishing-actions">
											<div id="nml-subscriber-status" class="nml-field misc-pub-section">
												<label for="nml_subscriber_status"><?php _e( 'Status', 'naked-mailing-list' ); ?></label>
												<select id="nml_subscriber_status" name="nml_subscriber_status">
													<?php foreach ( nml_get_subscriber_statuses() as $key => $name ) : ?>
														<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $subscriber->status, $key ); ?>><?php echo esc_html( $name ); ?></option>
													<?php endforeach; ?>
												</select>
											</div>

											<div id="nml-subscriber-signup-date" class="nml-field misc-pub-section">
												<label for="nml_subscriber_signup_date"><?php _e( 'Signup Date:', 'naked-mailing-list' ); ?></label>
												<span><?php echo ! empty( $subscriber->signup_date ) ? nml_format_mysql_date( $subscriber->signup_date, nml_full_date_time_format() ) : __( 'n/a', 'naked-mailing-list' ); ?></span>
											</div>

											<div id="nml-subscriber-confirm-date" class="nml-field misc-pub-section">
												<label for="nml_subscriber_confirm_date"><?php _e( 'Confirm Date:', 'naked-mailing-list' ); ?></label>
												<span><?php echo ! empty( $subscriber->confirm_date ) ? nml_format_mysql_date( $subscriber->confirm_date, nml_full_date_time_format() ) : __( 'n/a', 'naked-mailing-list' ); ?></span>
											</div>

											<div id="nml-subscriber-ip" class="nml-field misc-pub-section">
												<label for="nml_subscriber_ip"><?php _e( 'IP Address:', 'naked-mailing-list' ); ?></label>
												<span><?php echo $subscriber->ip; ?></span>
											</div>
										</div>
									</div>

									<div id="major-publishing-actions">
										<div id="delete-action">
											<?php if ( $subscriber->ID ) : ?>
												<a href="<?php echo esc_url( nml_get_admin_page_delete_subscriber( $subscriber->ID ) ); ?>"><?php _e( 'Delete Subscriber', 'naked-mailing-list' ); ?></a>
											<?php endif; ?>
										</div>
										<div id="publishing-action">
											<input type="submit" id="nml-save-subscriber" name="save_subscriber" class="button button-primary button-large" value="<?php esc_attr_e( 'Save', 'naked-mailing-list' ); ?>">
										</div>
									</div>
								</div>
							</div>
						</div>

						<?php do_action( 'nml_edit_subscriber_after_save_box', $subscriber ); ?>

						<div id="nml-subscriber-lists" class="postbox">
							<h2 class="hndle ui-sortable handle"><?php _e( 'Lists', 'naked-mailing-list' ); ?></h2>
							<div class="inside">
								Lists here
							</div>
						</div>

						<?php do_action( 'nml_edit_subscriber_after_lists_box', $subscriber ); ?>

						<div id="nml-subscriber-tags" class="postbox">
							<h2 class="hndle ui-sortable handle"><?php _e( 'Tags', 'naked-mailing-list' ); ?></h2>
							<div class="inside">
								Tags here
							</div>
						</div>

						<?php do_action( 'nml_edit_subscriber_after_tags_box', $subscriber ); ?>
					</div>
				</div>

				<div id="postbox-container-2" class="postbox-container">
					<?php do_action( 'nml_edit_subscriber_before_info_fields', $subscriber ); ?>

					<div class="postbox">
						<h2><?php _e( 'Subscriber Details', 'naked-mailing-list' ); ?></h2>
						<div class="inside">
							<?php do_action( 'nml_edit_subscriber_info_fields', $subscriber ); ?>
						</div>
					</div>

					<?php do_action( 'nml_edit_subscriber_after_info_fields', $subscriber ); ?>
				</div>
			</div>
		</div>
	</div>
	<?php

}