<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Studiofy_Project_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( array(
			'singular' => 'project',
			'plural'   => 'projects',
			'ajax'     => false,
		) );
	}

	/**
	 * Define Columns
	 */
	public function get_columns() {
		return array(
			'cb'       => '<input type="checkbox" />',
			'title'    => __( 'Project Name', 'studiofy-crm' ),
			'client'   => __( 'Client', 'studiofy-crm' ),
			'phase'    => __( 'Workflow Phase', 'studiofy-crm' ),
			'status'   => __( 'Status', 'studiofy-crm' ),
			'date'     => __( 'Date', 'studiofy-crm' ),
			'custom_1' => __( 'Custom 1', 'studiofy-crm' ),
			'custom_2' => __( 'Custom 2', 'studiofy-crm' ),
		);
	}

	/**
	 * Define Hidden Columns (Screen Options default)
	 */
	protected function get_default_hidden_columns() {
		return array( 'custom_2' );
	}

	/**
	 * Sortable Columns
	 */
	protected function get_sortable_columns() {
		return array(
			'title'  => array( 'title', false ),
			'status' => array( 'status', false ),
			'date'   => array( 'date', true ),
		);
	}

	/**
	 * Render Checkbox Column
	 */
	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="bulk_delete[]" value="%s" />', $item['ID'] );
	}

	/**
	 * Render Title Column with Row Actions
	 */
	protected function column_title( $item ) {
		$edit_url   = get_edit_post_link( $item['ID'] );
		$delete_url = get_delete_post_link( $item['ID'] );

		$actions = array(
			'edit'  => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), __( 'Edit', 'studiofy-crm' ) ),
			'trash' => sprintf( '<a href="%s" class="submitdelete">%s</a>', esc_url( $delete_url ), __( 'Trash', 'studiofy-crm' ) ),
		);

		return sprintf(
			'<strong><a class="row-title" href="%1$s">%2$s</a></strong>%3$s',
			esc_url( $edit_url ),
			esc_html( $item['post_title'] ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Default Column Renderer
	 */
	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'client':
				$cid = get_post_meta( $item['ID'], '_studiofy_client_id', true );
				if ( $cid ) {
					global $wpdb;
					$name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}studiofy_clients WHERE id=%d", $cid ) );
					return esc_html( $name );
				}
				return '—';
			case 'phase':
				$phase = get_post_meta( $item['ID'], '_studiofy_phase', true );
				return sprintf( '<span class="studiofy-pill phase-%s">%s</span>', sanitize_html_class( strtolower( $phase ) ), esc_html( $phase ) );
			case 'status':
				$status = get_post_meta( $item['ID'], '_studiofy_status', true );
				return sprintf( '<span class="studiofy-status-dot status-%s"></span> %s', sanitize_html_class( strtolower( $status ) ), esc_html( $status ) );
			case 'date':
				return get_the_date( get_option( 'date_format' ), $item['ID'] );
			case 'custom_1':
			case 'custom_2':
				return esc_html( get_post_meta( $item['ID'], '_studiofy_' . $column_name, true ) );
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Prepare Data
	 */
	public function prepare_items() {
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		
		// Map 'title' to post_title for WP_Query
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? sanitize_text_field( $_GET['orderby'] ) : 'date';
		$order   = ( ! empty( $_GET['order'] ) ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';

		$args = array(
			'post_type'      => 'studiofy_project',
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
			'orderby'        => $orderby,
			'order'          => $order,
			'post_status'    => 'any',
		);

		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = sanitize_text_field( $_REQUEST['s'] );
		}

		$query = new WP_Query( $args );

		$this->items = $query->posts;
		
		// Convert objects to array if needed, but WP_List_Table handles objects fine in column_default
		// Logic above assumes $item is an array, but WP_Query returns objects. 
		// Converting to array for consistency with previous codebase patterns:
		$this->items = json_decode( json_encode( $query->posts ), true );

		$this->set_pagination_args( array(
			'total_items' => $query->found_posts,
			'per_page'    => $per_page,
			'total_pages' => $query->max_num_pages,
		) );
	}
}
