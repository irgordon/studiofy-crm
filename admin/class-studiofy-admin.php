<?php
declare(strict_types=1);

class Studiofy_Admin {
    private string $plugin_name;
    private string $version;

    public function __construct( string $plugin_name, string $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    public function enqueue_styles(): void {
        wp_enqueue_style( $this->plugin_name, STUDIOFY_URL . 'admin/css/studiofy-admin.css', array(), $this->version, 'all' );
    }

    public function add_plugin_admin_menu(): void {
        add_menu_page( 'Studiofy', 'Studiofy', 'view_studiofy_crm', 'studiofy-dashboard', array($this, 'route_dashboard'), 'dashicons-camera', 6 );
        add_submenu_page( 'studiofy-dashboard', 'Projects', 'Projects', 'edit_studiofy_client', 'studiofy-projects', array($this, 'route_projects') );
        add_submenu_page( 'studiofy-dashboard', 'Leads', 'Leads', 'edit_studiofy_client', 'studiofy-leads', array($this, 'route_leads') );
        add_submenu_page( 'studiofy-dashboard', 'Invoices', 'Invoices', 'manage_studiofy_invoices', 'studiofy-invoices', array($this, 'route_invoices') );
        add_submenu_page( 'studiofy-dashboard', 'Scheduling', 'Scheduling', 'view_studiofy_crm', 'studiofy-scheduling', array($this, 'route_scheduling') );
    }

    // --- ROUTERS ---
    public function route_dashboard(): void {
        // Simple Dashboard Overview
        echo '<div class="wrap"><h1>Studiofy Dashboard</h1><p>Welcome to your studio hub.</p></div>';
    }

    public function route_projects(): void {
        require_once STUDIOFY_PATH . 'includes/modules/class-studiofy-projects.php';
        (new Studiofy_Projects())->render();
    }

    public function route_leads(): void {
        // Basic Leads Table Logic
        global $wpdb;
        $leads = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}studiofy_leads ORDER BY created_at DESC");
        require_once STUDIOFY_PATH . 'admin/partials/view-leads-list.php';
    }

    public function route_invoices(): void {
        require_once STUDIOFY_PATH . 'includes/modules/class-studiofy-invoices.php';
        (new Studiofy_Invoices())->render();
    }

    public function route_scheduling(): void {
        require_once STUDIOFY_PATH . 'admin/partials/view-scheduling.php';
    }

    // --- HANDLERS ---
    public function handler_save_project(): void {
        check_admin_referer('save_project');
        global $wpdb;
        
        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'client_id' => intval($_POST['client_id']),
            'status' => sanitize_text_field($_POST['status']),
            'workflow_phase' => sanitize_text_field($_POST['workflow_phase']),
            'notes' => sanitize_textarea_field($_POST['notes'])
        ];

        if(!empty($_POST['id'])) {
            $wpdb->update($wpdb->prefix.'studiofy_projects', $data, ['id'=>intval($_POST['id'])]);
        } else {
            $wpdb->insert($wpdb->prefix.'studiofy_projects', $data);
        }
        wp_redirect(admin_url('admin.php?page=studiofy-projects'));
        exit;
    }

    public function handler_delete_item(): void {
        if(!current_user_can('edit_studiofy_client')) wp_die('Unauthorized');
        global $wpdb;
        $table = sanitize_key($_GET['table']); // e.g., 'studiofy_projects'
        $id = intval($_GET['id']);
        
        // Safety check: Foreign Keys
        if($table === 'studiofy_projects') {
             $has_inv = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM {$wpdb->prefix}studiofy_invoices WHERE project_id=%d", $id));
             if($has_inv > 0) wp_die("Cannot delete project with active invoices.");
        }

        $wpdb->delete($wpdb->prefix . $table, ['id' => $id]);
        wp_redirect(wp_get_referer());
        exit;
    }
}
