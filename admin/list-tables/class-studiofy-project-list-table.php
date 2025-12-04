<?php
if ( ! class_exists( 'WP_List_Table' ) ) require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

class Studiofy_Contract_List_Table extends WP_List_Table {
	public function __construct() {
		parent::__construct( array( 'singular' => 'contract', 'plural' => 'contracts', 'ajax' => false ) );
	}

	public function get_columns() {
		return array(
			'cb'     => '<input type="checkbox" />',
			'title'  => __( 'Contract Title', 'studiofy-crm' ),
			'client' => __( 'Client', 'studiofy-crm' ),
			'status' => __( 'Status', 'studiofy-crm' ),
			'date'   => __( 'Date', 'studiofy-crm' ),
		);
	}

	// Bulk Actions
	public function get_bulk_actions() {
		return array(
			'bulk-delete' => 'Delete',
			'bulk-print'  => 'Print Selected', // Logic handled in admin-post.php
		);
	}

	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="bulk_ids[]" value="%s" />', $item['ID'] );
	}

	protected function column_title( $item ) {
		$edit_url  = get_edit_post_link( $item['ID'] );
		$print_url = add_query_arg( 'print_contract', $item['ID'], home_url() );

		$actions = array(
			'edit'  => sprintf( '<a href="%s">Edit</a>', $edit_url ),
			'print' => sprintf( '<a href="%s" target="_blank">Print PDF</a>', esc_url( $print_url ) ),
			'trash' => sprintf( '<a href="%s" class="submitdelete">Trash</a>', get_delete_post_link( $item['ID'] ) ),
		);

		return sprintf( '<strong><a class="row-title" href="%1$s">%2$s</a></strong>%3$s', $edit_url, esc_html( $item['post_title'] ), $this->row_actions( $actions ) );
	}

	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'status':
				$s = get_post_meta( $item['ID'], '_studiofy_status', true );
				$color = ( 'Signed' === $s ) ? 'green' : 'orange';
				return "<span style='color:$color;font-weight:bold;'>$s</span>";
			case 'date': return get_the_date( '', $item['ID'] );
			default: return print_r( $item, true );
		}
	}

	public function prepare_items() {
		$args = array( 'post_type' => 'studiofy_contract', 'posts_per_page' => 20, 'paged' => $this->get_pagenum() );
		$query = new WP_Query( $args );
		$this->items = json_decode( json_encode( $query->posts ), true );
		$this->set_pagination_args( array( 'total_items' => $query->found_posts, 'per_page' => 20 ) );
	}
}
