<?php
/**
 * List Functions
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
 * Get lists
 *
 * @param array $args Arguments to override the defaults.
 *
 * @since 1.0
 * @return array
 */
function nml_get_lists( $args = array() ) {
	$default = array(
		'type' => 'list'
	);

	$args = wp_parse_args( $args, $default );

	$lists = naked_mailing_list()->lists->get_lists( $args );

	return $lists;
}

/**
 * Get tags
 *
 * @param array $args Arguments to override the defaults.
 *
 * @since 1.0
 * @return array
 */
function nml_get_tags( $args = array() ) {
	$default = array(
		'type' => 'tag'
	);

	$args = wp_parse_args( $args, $default );

	$tags = naked_mailing_list()->lists->get_lists( $args );

	return $tags;
}

/**
 * Get subscriber lists
 *
 * @see   wp_get_object_terms()
 *
 * @param int         $subscriber_id ID of the subscriber to get the lists for.
 * @param string|bool $type          Type of lists to retrieve (`list` or `tag`), or false for all.
 * @param array       $args          Query arguments to override the defaults.
 *
 * @since 1.0
 * @return array|false Array of list objects or false on failure.
 */
function nml_get_subscriber_lists( $subscriber_id, $type = false, $args = array() ) {
	global $wpdb;

	$default_args = array(
		'orderby' => 'name',
		'order'   => 'ASC',
		'fields'  => 'all'
	);

	$args = wp_parse_args( $args, $default_args );

	$relationship_table = naked_mailing_list()->list_relationships->table_name;
	$list_table         = naked_mailing_list()->lists->table_name;

	$where_type = $type ? $wpdb->prepare( " AND l.type = %s", sanitize_text_field( $type ) ) : '';

	// Select this.
	$select_this = '';
	if ( 'all' == $args['fields'] ) {
		$select_this = 'l.*';
	} elseif ( 'ids' == $args['fields'] ) {
		$select_this = 'l.ID';
	} elseif ( 'names' == $args['fields'] ) {
		$select_this = 'l.name';
	}

	// Orderby
	$orderby = $args['orderby'];
	$order   = $args['order'];

	if ( in_array( $orderby, array( 'ID', 'type', 'name', 'count' ) ) ) {
		$orderby = "l.$orderby";
	} elseif ( 'none' === $orderby ) {
		$orderby = '';
		$order   = '';
	} else {
		$orderby = 'l.ID';
	}

	if ( ! empty( $orderby ) ) {
		$orderby = "ORDER BY $orderby";
	}

	$order = strtoupper( $order );
	if ( '' !== $order && ! in_array( $order, array( 'ASC', 'DESC' ) ) ) {
		$order = 'ASC';
	}

	$query = $wpdb->prepare( "SELECT $select_this FROM $list_table AS l INNER JOIN $relationship_table AS lr on l.ID = lr.list_id WHERE lr.subscriber_id = %d $where_type $orderby $order", absint( $subscriber_id ) );
	$lists = array();

	if ( 'all' == $args['fields'] ) {
		$lists = $wpdb->get_results( $query );
	} elseif ( 'ids' == $args['fields'] || 'names' == $args['fields'] ) {
		$lists = $wpdb->get_col( $query );
	}

	return $lists;
}

/**
 * Create subscriber and list relationships
 *
 * Relates a subscriber to a list and list type. Creates the
 * list if it doesn't already exisl.
 *
 * @param int              $subscriber_id ID of the subscriber to related list(s) to.
 * @param array|int|string $lists         Single list ID or array of IDs.
 * @param string           $type          List type (`list` or `tag`).
 * @param bool             $append        If false, will delete the difference of lists.
 *
 * @since 1.0
 * @return array|WP_Error List IDs of the affected terms.
 */
function nml_set_subscriber_lists( $subscriber_id, $lists, $type, $append = false ) {

	if ( ! is_numeric( $subscriber_id ) || ! $subscriber_id > 0 ) {
		return new WP_Error( 'invalid-subscriber', __( 'Invalid subscriber ID.', 'naked-mailing-list' ) );
	}

	if ( ! is_array( $lists ) ) {
		$lists = array( $lists );
	}

	// Get existing lists.
	if ( ! $append ) {
		$old_list_ids = nml_get_subscriber_lists( $subscriber_id, $type, array( 'fields' => 'ids' ) );
	} else {
		$old_list_ids = array();
	}

	$all_list_ids = array();

	foreach ( (array) $lists as $list ) {

		// $list is either a list name or ID.

		if ( ! strlen( trim( $list ) ) ) {
			continue;
		}

		if ( is_int( $list ) ) {

			// We have a list ID.

			$list_id = absint( $list );

			// If this term ID doesn't exist - skip it.
			if ( ! naked_mailing_list()->lists->exists( $list_id ) ) {
				continue;
			}

			// Update the count.
			nml_update_list_count( $list_id );

			$all_list_ids[] = $list_id;

		} else {

			// We have a list name.

			// Check to see if it already exists.
			$existing_list = naked_mailing_list()->lists->get_list_by( 'name', $list );

			if ( $existing_list ) {
				$list_id = $existing_list->ID;
				nml_update_list_count( $list_id );
			} else {
				// Create new list.
				$list_id = naked_mailing_list()->lists->add( array(
					'name'  => sanitize_text_field( $list ),
					'type'  => sanitize_text_field( $type ),
					'count' => 1
				) );
			}

			// Error adding it.
			if ( ! $list_id ) {
				continue;
			}

			$all_list_ids[] = $list_id;

		}

		// If the list relationship already exists - let's move on.
		if ( nml_relationship_exists( $subscriber_id, $list_id ) ) {
			continue;
		}

		// Otherwise, create the relationship.
		naked_mailing_list()->list_relationships->add( array(
			'list_id'       => absint( $list_id ),
			'subscriber_id' => absint( $subscriber_id )
		) );

	}

	if ( ! $append ) {

		// Delete existing list relationships.
		$delete_list_relationships = array_diff( $old_list_ids, $all_list_ids );

		if ( $delete_list_relationships ) {
			foreach ( $delete_list_relationships as $list_id ) {
				// Delete the relationship.
				$relationship = nml_get_relationship( array(
					'subscriber_id' => $subscriber_id,
					'list_id'       => $list_id
				) );

				if ( $relationship ) {
					naked_mailing_list()->list_relationships->delete( absint( $relationship->ID ) ); // @todo do this without a foreach

					// Reduce the count.
					nml_update_list_count( $list_id );
				}
			}
		}

	}

	return $all_list_ids;

}

/**
 * Relationship Exists
 *
 * Checks whether a relationship exists between a book ID and a list ID.
 *
 * @param int $subscriber_id
 * @param int $list_id
 *
 * @since 1.0
 * @return bool
 */
function nml_relationship_exists( $subscriber_id, $list_id ) {
	if ( ! is_numeric( $subscriber_id ) || ! is_numeric( $list_id ) ) {
		return false;
	}

	$args   = array(
		'list_id'       => absint( $list_id ),
		'subscriber_id' => absint( $subscriber_id )
	);
	$result = naked_mailing_list()->list_relationships->get_relationships( $args );

	return ( is_array( $result ) && count( $result ) ) ? true : false;
}

/**
 * Get Relationship
 *
 * Always returns one result.
 *
 * @param int $book_id
 * @param int $list_id
 *
 * @since 1.0
 * @return object|bool Relationship object or false if none is found.
 */
function nml_get_relationship( $args = array() ) {
	$defaults = array(
		'number' => 1
	);

	$args = wp_parse_args( $args, $defaults );

	$result = naked_mailing_list()->list_relationships->get_relationships( $args );

	if ( is_array( $result ) && ! empty( $result ) && array_key_exists( 0, $result ) ) {
		return $result[0];
	}

	return false;
}

/**
 * Update List Count
 *
 * @param int $list_id ID of the list to update the count for.
 *
 * @since 1.0
 * @return int|bool Updated term ID on success, or false on failure.
 */
function nml_update_list_count( $list_id ) {
	$new_count = naked_mailing_list()->list_relationships->count( array(
		'list_id' => $list_id
	) );

	if ( false === $new_count ) {
		return false;
	}

	$args = array(
		'ID'    => absint( $list_id ),
		'count' => absint( $new_count )
	);

	return naked_mailing_list()->lists->add( $args );
}