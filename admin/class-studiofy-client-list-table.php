<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Studiofy_Client_List_Table extends WP_List_Table {
	public function prepare_items(): void {
		$this->_column_headers = array( $this->get_columns(), array(), array() );
		$this->items           = $this->get_data();
	}

	public function get_columns(): array {
		return array(
			'name'   => __( 'Name', 'studiofy-crm' ),
			'email'  => __( 'Email', 'studiofy-crm' ),
			'status' => __( 'Status', 'studiofy-crm' ),
		);
	}

	private function get_data(): array {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}studiofy_clients ORDER BY created_at DESC", ARRAY_A );
	}

	public function column_default( object|array $item, string $column_name ): string {
		return esc_html( (string) $item[ $column_name ] );
	}

	public function column_status( array $item ): string {
		return '<span class="studiofy-badge status-' . esc_attr( $item['status'] ) . '">' . esc_html( ucfirst( $item['status'] ) ) . '</span>';
	}
}
