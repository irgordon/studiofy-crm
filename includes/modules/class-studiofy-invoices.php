<?php
declare(strict_types=1);

class Studiofy_Invoices {
    public function render(): void {
        if ( isset($_POST['export_csv']) ) {
            $this->export_csv();
            return;
        }

        $action = $_GET['action'] ?? 'list';
        if ( $action === 'new' ) $this->render_form();
        else $this->render_list();
    }

    private function render_list(): void {
        global $wpdb;
        $invoices = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}studiofy_invoices ORDER BY created_at DESC");
        require_once STUDIOFY_PATH . 'admin/partials/view-invoices-list.php';
    }

    private function render_form(): void {
        global $wpdb;
        // Auto-Generate Invoice ID
        $last_id = $wpdb->get_var("SELECT MAX(id) FROM {$wpdb->prefix}studiofy_invoices");
        $next_num = 'INV-' . str_pad((string)($last_id + 1), 5, '0', STR_PAD_LEFT);
        
        $projects = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}studiofy_projects ORDER BY created_at DESC");
        $contracts = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}studiofy_contracts ORDER BY created_at DESC");
        
        require_once STUDIOFY_PATH . 'admin/partials/view-invoices-form.php';
    }

    private function export_csv(): void {
        global $wpdb;
        $filename = 'invoices_' . date('Y-m-d') . '.csv';
        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment;filename=' . $filename );
        $fp = fopen( 'php://output', 'w' );
        fputcsv( $fp, array( 'ID', 'Invoice #', 'Amount', 'Status', 'Date', 'Type' ) );
        $rows = $wpdb->get_results( "SELECT id, invoice_number, amount, status, created_at, payment_type FROM {$wpdb->prefix}studiofy_invoices", ARRAY_A );
        foreach ( $rows as $row ) fputcsv( $fp, $row );
        fclose( $fp );
        exit;
    }
}
