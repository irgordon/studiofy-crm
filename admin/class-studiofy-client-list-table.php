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

	public function get_columns() {
		return array(
			'cb'             => '<input type="checkbox" />',
			'title'          => __( 'Project Name', 'studiofy-crm' ),
			'client'         => __( 'Client', 'studiofy-crm' ),
			'type'           => __( 'Type', 'studiofy-crm' ),
			'status'         => __( 'Status', 'studiofy-crm' ),
			'phase'          => __( 'Phase', 'studiofy-crm' ),
			'invoice_id'     => __( 'Invoice ID', 'studiofy-crm' ),
			'invoice_status' => __( 'Inv. Status', 'studiofy-crm' ),
		);
	}

	protected function get_sortable_columns() {
		return array(
			'title'  => array( 'title', false ),
			'status' => array( 'status', false ),
			'type'   => array( 'type', false ),
		);
	}

	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="bulk_delete[]" value="%s" />', $item['ID'] );
	}

	protected function column_title( $item ) {
		$edit_url = get_edit_post_link( $item['ID'] );
		$actions  = array(
			'edit'  => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), __( 'Edit', 'studiofy-crm' ) ),
			'trash' => sprintf( '<a href="%s" class="submitdelete">%s</a>', get_delete_post_link( $item['ID'] ), __( 'Trash', 'studiofy-crm' ) ),
		);
		return sprintf( '<strong><a class="row-title" href="%1$s">%2$s</a></strong>%3$s', esc_url( $edit_url ), esc_html( $item['post_title'] ), $this->row_actions( $actions ) );
	}

	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'client':
				$cid = get_post_meta( $item['ID'], '_studiofy_client_id', true );
				if ( $cid ) {
					global $wpdb;
					$client = $wpdb->get_row( $wpdb->prepare( "SELECT id, name FROM {$wpdb->prefix}studiofy_clients WHERE id=%d", $cid ) );
					if ( $client ) {
						// Link to Client Edit/Hub Page (Assuming you have a page for client details)
						// For now, we link to the Lead edit page if a matching lead exists, or just show name
						return sprintf( '<strong>%s</strong>', esc_html( $client->name ) );
					}
				}
				return '—';

			case 'type':
				return esc_html( get_post_meta( $item['ID'], '_studiofy_project_type', true ) );

			case 'status':
				$st = get_post_meta( $item['ID'], '_studiofy_status', true );
				return sprintf( '<span class="studiofy-pill status-%s">%s</span>', sanitize_html_class( strtolower( $st ) ), esc_html( $st ) );

			case 'phase':
				return esc_html( get_post_meta( $item['ID'], '_studiofy_phase', true ) );

			case 'invoice_id':
				$inv_post_id = get_post_meta( $item['ID'], '_studiofy_linked_invoice_id', true );
				if ( $inv_post_id ) {
					return sprintf( '<a href="%s">#%s</a>', get_edit_post_link( $inv_post_id ), esc_html( get_the_title( $inv_post_id ) ) );
				}
				return '—';

			case 'invoice_status':
				$inv_post_id = get_post_meta( $item['ID'], '_studiofy_linked_invoice_id', true );
				if ( $inv_post_id ) {
					$st = get_post_meta( $inv_post_id, '_studiofy_status', true );
					// Billed, Deposit Paid, Installments, Cancelled, Refunded
					$color = 'grey';
					if ( 'Paid' === $st || 'Paid In Full' === $st ) $color = 'green';
					if ( 'Partial' === $st || 'Deposit Paid' === $st ) $color = 'orange';
					if ( 'Overdue' === $st ) $color = 'red';
					
					return sprintf( '<span style="color:%s; font-weight:bold;">%s</span>', $color, esc_html( $st ) );
				}
				return '—';

			default:
				return print_r( $item, true );
		}
	}

	public function prepare_items() {
		$per_page = 20;
		$current_page = $this->get_pagenum();
		
		$args = array(
			'post_type'      => 'studiofy_project',
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
			'post_status'    => 'any',
		);

		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = sanitize_text_field( $_REQUEST['s'] );
		}

		$query = new WP_Query( $args );
		$this->items = $query->posts;
		$this->set_pagination_args( array(
			'total_items' => $query->found_posts,
			'per_page'    => $per_page,
			'total_pages' => $query->max_num_pages,
		) );
	}
}
