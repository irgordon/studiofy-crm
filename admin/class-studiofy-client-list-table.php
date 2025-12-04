<?php
if ( ! class_exists( 'WP_List_Table' ) ) require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class Studiofy_Client_List_Table extends WP_List_Table {
    public function prepare_items() {
        $this->_column_headers = array( $this->get_columns(), array(), array() );
        $this->items = $this->get_data();
    }

    public function get_columns() {
        return array( 'name' => 'Name', 'email' => 'Email', 'status' => 'Status' );
    }

    private function get_data() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}studiofy_clients ORDER BY created_at DESC", ARRAY_A );
    }

    public function column_default( $item, $column_name ) {
        return esc_html( $item[ $column_name ] );
    }
    
    public function column_status( $item ) {
        return '<span style="background:#e5f6fd; color:#0c8dbf; padding:3px 8px; border-radius:10px;">'.esc_html(ucfirst($item['status'])).'</span>';
    }
}
