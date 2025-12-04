<?php
declare(strict_types=1);

class Studiofy_Projects {
    public function render(): void {
        $action = $_GET['action'] ?? 'list';
        if ( $action === 'new' || $action === 'edit' ) {
            $this->render_form();
        } else {
            $this->render_list();
        }
    }

    private function render_list(): void {
        global $wpdb;
        $orderby = $_GET['orderby'] ?? 'created_at';
        $order   = $_GET['order'] ?? 'DESC';
        
        // Whitelist sorting
        if(!in_array($orderby, ['title','status','created_at'])) $orderby = 'created_at';
        
        $projects = $wpdb->get_results( "SELECT p.*, c.name as client_name FROM {$wpdb->prefix}studiofy_projects p LEFT JOIN {$wpdb->prefix}studiofy_clients c ON p.client_id = c.id ORDER BY p.$orderby $order" );

        // Kanban Stats
        $counts = [ 'active' => 0, 'pending' => 0, 'complete' => 0 ];
        foreach($projects as $p) {
            if($p->status === 'Complete') $counts['complete']++;
            elseif($p->status === 'New') $counts['pending']++;
            else $counts['active']++;
        }

        require_once STUDIOFY_PATH . 'admin/partials/view-projects-list.php';
    }

    private function render_form(): void {
        global $wpdb;
        $project = null;
        
        if ( isset($_GET['id']) ) {
            $id = intval($_GET['id']);
            $project = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_projects WHERE id=%d", $id) );
        }
        
        $clients = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}studiofy_clients ORDER BY name ASC");
        require_once STUDIOFY_PATH . 'admin/partials/view-projects-form.php';
    }
}
