<?php

/**
 * Newsletter Table Class
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

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class NML_Newsletter_Table
 *
 * Renders the newsletter table.
 *
 * @since 1.0
 */
class NML_Newsletter_Table extends WP_List_Table {

	/**
	 * Number of items per page
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $per_page = 20;

	/**
	 * Number of newsletters found
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $count = 0;

	/**
	 * Total number of newsletters
	 *
	 * @var int
	 * @access public
	 * @since  1.0
	 */
	public $total = 0;

	/**
	 * The arguments for the data set
	 *
	 * @var array
	 * @access public
	 * @since  1.0
	 */
	public $args = array();

	/**
	 * Display delete message
	 *
	 * @var bool
	 * @access private
	 * @since  1.0
	 */
	private $display_delete_message = false;

	/**
	 * NML_Newsletter_Table constructor.
	 *
	 * @see    WP_List_Table::__construct()
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {

		global $status, $page;

		parent::__construct( array(
			'singular' => esc_html__( 'Newsletter', 'naked-mailing-list' ),
			'plural'   => esc_html__( 'Newsletters', 'naked-mailing-list' ),
			'ajax'     => false
		) );

	}

	/**
	 * Show the search field
	 *
	 * @param string $text     Label for the search box.
	 * @param string $input_id ID of the search box.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function search_box( $text, $input_id ) {

		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '">';
		}

		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '">';
		}

		$search = isset( $_REQUEST['s'] ) ? wp_unslash( $_REQUEST['s'] ) : '';

		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php esc_html_e( 'Search', 'naked-mailing-list' ); ?></label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search', 'naked-mailing-list' ); ?>">

			<?php submit_button( $text, 'button', false, false, array( 'ID' => 'search-submit' ) ); ?>
		</p>
		<?php

	}

	/**
	 * Retrieve the view types
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_views() {
		$base = nml_get_admin_page_newsletters();

		$current  = isset( $_GET['status'] ) ? $_GET['status'] : '';
		$statuses = nml_get_newsletter_statuses();
		$counts   = $this->get_counts();

		$views = array(
			'all' => sprintf( '<a href="%s"%s>%s</a>', remove_query_arg( 'status', $base ), $current === 'all' || $current == '' ? ' class="current"' : '', __( 'All', 'naked-mailing-list' ) . '&nbsp;<span class="count">(' . $counts['total'] . ')</span>' )
		);

		foreach ( $statuses as $id => $name ) {
			$views[ $id ] = sprintf( '<a href="%s"%s>%s</a>', add_query_arg( 'status', urlencode( $id ), $base ), $current === $id ? ' class="current"' : '', $name . '&nbsp;<span class="count">(' . $counts[ $id ] . ')</span>' );
		}

		return $views;
	}

	/**
	 * Get status counts
	 *
	 * Returns an array of all the statuses and their number of results.
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_counts() {

		$counts   = array(
			'total' => naked_mailing_list()->newsletters->count()
		);
		$statuses = nml_get_newsletter_statuses();

		foreach ( $statuses as $id => $name ) {
			$counts[ $id ] = naked_mailing_list()->newsletters->count( array(
				'status' => $id
			) );
		}

		return $counts;

	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @access protected
	 * @since  1.0
	 * @return string
	 */
	protected function get_primary_column_name() {
		return 'subject';
	}

	/**
	 * Renders most of the columns in the list table.
	 *
	 * @param object $item        Contains all the data of the newsletter.
	 * @param string $column_name The name of the column.
	 *
	 * @access public
	 * @since  1.0
	 * @return string Column name
	 */
	public function column_default( $item, $column_name ) {

		$newsletter = new NML_Newsletter( $item->ID );
		$value      = '';

		switch ( $column_name ) {

			case 'status' :
				$value = $item->status;

				if ( 'sending' == $item->status && ! empty( $newsletter->subscriber_count ) ) {
					ob_start();
					?>
					<div class="nml-progress nml-progress-striped">
						<div style="width: <?php echo esc_attr( $newsletter->get_percentage_sent() ); ?>%"></div>
					</div>
					<?php
					$value = ob_get_clean();
				}
				break;

			case 'lists' :
				$lists = $newsletter->get_lists( 'list' );

				if ( is_array( $lists ) && ! empty( $lists ) ) {
					$list_names = array();

					foreach ( $lists as $list ) {
						$list_names[] = '<a href="' . esc_url( add_query_arg( array( 'list' => absint( $list->ID ) ) ) ) . '">' . esc_html( $list->name ) . '</a>';
					}

					$value = implode( ', ', $list_names );
				} else {
					$value = '&ndash;';
				}
				break;

			case 'updated_date' :
				if ( ! empty( $item->updated_date ) ) {
					$value = nml_format_mysql_date( $item->updated_date );
				}
				break;

			default :
				$value = isset( $item->$column_name ) ? $item->$column_name : null;
				break;

		}

		return apply_filters( 'nml_newsletter_table_column_' . $column_name, $value, $item );

	}

	/**
	 * Render Checkbox Column
	 *
	 * @param object $item Contains all the data of the newsletter.
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function column_cb( $item ) {

		if ( ! current_user_can( 'delete_posts' ) ) {
			return;
		}

		?>
		<label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $item->ID ); ?>">
			<?php _e( 'Select this newsletter', 'naked-mailing-list' ) ?>
		</label>
		<input id="cb-select-<?php echo esc_attr( $item->ID ); ?>" type="checkbox" name="newsletters[]" value="<?php echo esc_attr( $item->ID ); ?>">
		<?php

	}

	/**
	 * Render Column Name
	 *
	 * @param object $item Contains all the data of the newsletter.
	 *
	 * @access public
	 * @since  1.0
	 * @return string
	 */
	public function column_subject( $item ) {
		$edit_url = nml_get_admin_page_edit_newsletter( $item->ID );
		$name     = '<a href="' . esc_url( $edit_url ) . '" class="row-title" aria-label="' . esc_attr( sprintf( '%s (Edit)', $item->subject ) ) . '">' . $item->subject . '</a>';
		$actions  = array(
			'edit'   => '<a href="' . esc_url( $edit_url ) . '">' . __( 'Edit', 'naked-mailing-list' ) . '</a>',
			'delete' => '<a href="' . esc_url( nml_get_admin_page_delete_newsletter( $item->ID ) ) . '">' . __( 'Delete', 'naked-mailing-list' ) . '</a>',
			'view'   => '<a href="' . esc_url( nml_get_newsletter_preview_url( $item->ID ) ) . '" target="_blank">' . __( 'Preview', 'naked-mailing-list' ) . '</a>',
		);

		return $name . $this->row_actions( $actions );
	}

	/**
	 * Get Columns
	 *
	 * Retrieves the column IDs and names.
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox">',
			'subject'      => __( 'Subject', 'naked-mailing-list' ),
			'status'       => __( 'Status', 'naked-mailing-list' ),
			'lists'        => __( 'Lists', 'naked-mailing-list' ),
			'updated_date' => __( 'Last Modified', 'naked-mailing-list' )
		);

		return apply_filters( 'nml_newsletter_table_columns', $columns );
	}

	/**
	 * Get the sortable columns.
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'subject'      => array( 'subject', true ),
			'status'       => array( 'status', true ),
			'updated_date' => array( 'updated_date', true )
		);
	}

	/**
	 * Table Navigation
	 *
	 * Generate the table navigation above or below the table.
	 *
	 * @param string $which
	 *
	 * @access protected
	 * @since  1.0
	 * @return void
	 */
	protected function display_tablenav( $which ) {

		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}

		// Display 'delete' success message.
		if ( 'top' == $which && true === $this->display_delete_message ) {
			?>
			<div id="message" class="updated notice notice-success">
				<p><?php _e( 'Newsletters successfully deleted.', 'naked-mailing-list' ); ?></p>
			</div>
			<?php
		}

		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<?php if ( $this->has_items() ): ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
			<?php endif;
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear"/>
		</div>
		<?php

	}

	/**
	 * Get Bulk Actions
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete Permanently', 'naked-mailing-list' )
		);

		return apply_filters( 'nml_newsletters_table_bulk_actions', $actions );
	}

	/**
	 * Process Bulk Actions
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function process_bulk_actions() {

		if ( 'delete' == $this->current_action() ) {

			// Check nonce.
			if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'bulk-' . $this->_args['plural'] ) ) {
				wp_die( __( 'Failed security check.', 'naked-mailing-list' ) );
			}

			// Checek capability.
			if ( ! current_user_can( 'delete_posts' ) ) {
				wp_die( __( 'You don\'t have permission to delete newsletters.', 'naked-mailing-list' ) );
			}

			if ( isset( $_GET['newsletters'] ) && is_array( $_GET['newsletters'] ) && count( $_GET['newsletters'] ) ) {
				naked_mailing_list()->newsletters->delete_by_ids( $_GET['newsletters'] );

				// Display the delete message.
				$this->display_delete_message = true;
			}

		}

	}

	/**
	 * Retrieve the current page number.
	 *
	 * @access public
	 * @since  1.0
	 * @return int
	 */
	public function get_paged() {
		return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	}

	/**
	 * Retrieves the search query string.
	 *
	 * @access public
	 * @since  1.0
	 * @return bool|string Search query or false if none.
	 */
	public function get_search() {
		return ! empty( $_GET['s'] ) ? urldecode( trim( $_GET['s'] ) ) : false;
	}

	/**
	 * Build all the newsletter data.
	 *
	 * @access public
	 * @since  1.0
	 * @return array Array of newsletters.
	 */
	public function newsletter_data() {

		$paged   = $this->get_paged();
		$offset  = $this->per_page * ( $paged - 1 );
		$order   = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'ID';

		$args = array(
			'number'  => $this->per_page,
			'offset'  => $offset,
			'order'   => $order,
			'orderby' => $orderby,
		);

		// Filter by subject
		if ( isset( $_GET['s'] ) ) {
			$args['subject'] = sanitize_text_field( $_GET['s'] );
		}

		// Filter by status
		if ( isset( $_GET['status'] ) ) {
			$args['status'] = sanitize_text_field( $_GET['status'] );
		}

		// Filter by list
		if ( isset( $_GET['list'] ) ) {
			$args['list'] = absint( $_GET['list'] );
		}

		$this->args  = $args;
		$newsletters = nml_get_newsletters( $args );

		return $newsletters;

	}

	/**
	 * Prepare Items
	 *
	 * Setup the final data for the table.
	 *
	 * @uses   NML_Newsletter_Table::get_columns()
	 * @uses   WP_List_Table::get_sortable_columns()
	 * @uses   NML_Newsletter_Table::newsletter_data()
	 * @uses   WP_List_Table::set_pagination_args()
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function prepare_items() {

		// Process bulk actions.
		$this->process_bulk_actions();

		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $this->newsletter_data();

		$this->total = naked_mailing_list()->newsletters->count( $this->args );

		$this->set_pagination_args( array(
			'total_items' => $this->total,
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $this->total / $this->per_page )
		) );

	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since  1.0
	 * @access public
	 * @return void
	 */
	public function no_items() {
		printf(
			__( 'No newsletters found. Would you like to %sadd one?%', 'naked-mailing-list' ),
			'<a href="' . esc_url( nml_get_admin_page_add_newsletter() ) . '">',
			'</a>'
		);
	}

}