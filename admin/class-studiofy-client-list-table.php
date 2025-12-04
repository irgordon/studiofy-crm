<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Studiofy_Client_List_Table extends WP_List_Table {

	/**
	 * Prepare the items for the table to process
	 */
	public function prepare_items() {
		$this->_column_headers = array( $this->get_columns(), array(), array() );
		$this->items           = $this->get_data();
		
		// Optional: Add pagination setup here if needed later
		// $total_items = count($this->items);
		// $per_page = 20;
		// $this->set_pagination_args( array(
		//    'total_items' => $total_items,
		//    'per_page'    => $per_page,
		//    'total_pages' => ceil( $total_items / $per_page )
		// ) );
	}

	/**
	 * Define the columns that are going to be used in the table
	 */
	public function get_columns() {
		return array(
			'name'   => __( 'Name', 'studiofy-crm' ),
			'email'  => __( 'Email', 'studiofy-crm' ),
			'status' => __( 'Status', 'studiofy-crm' ),
		);
	}

	/**
	 * Retrieve client data from the database
	 */
	private function get_data() {
		global $wpdb;
		// Fetch data as an associative array
		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}studiofy_clients ORDER BY created_at DESC", ARRAY_A );
	}

	/**
	 * Default Column Render
	 * FIXED: Removed type hints to match WP_List_Table::column_default($item, $column_name)
	 */
	public function column_default( $item, $column_name ) {
		// Ensure $item is an array before accessing
		if ( is_array( $item ) && isset( $item[ $column_name ] ) ) {
			return esc_html( (string) $item[ $column_name ] );
		}
		return ''; 
	}

	/**
	 * Render the Status column with Badges
	 * FIXED: Removed type hints to match generic call structure
	 */
	public function column_status( $item ) {
		$status = isset( $item['status'] ) ? $item['status'] : '';
		return sprintf(
			'<span class="studiofy-badge status-%s">%s</span>',
			esc_attr( $status ),
			esc_html( ucfirst( $status ) )
		);
	}
}
