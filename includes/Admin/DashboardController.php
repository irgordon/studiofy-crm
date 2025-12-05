<?php
declare(strict_types=1);
namespace Studiofy\Admin;

class DashboardController {
    public function render_page(): void {
        global $wpdb;
        $stats = get_transient('studiofy_dashboard_stats');
        if (false === $stats) {
            $stats = [
                'clients' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_clients WHERE status='Active'"),
                'projects' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_projects WHERE status='in_progress'"),
                'appts' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_bookings WHERE status='pending'"),
                'invoices' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_invoices WHERE status='Draft'")
            ];
            set_transient('studiofy_dashboard_stats', $stats, 3600); // 1 Hour
        }
        ?>
        <div class="wrap studiofy-dark-theme">
            <h1 style="color:#fff;">Dashboard</h1>
            <div class="studiofy-dashboard-grid">
                <div class="studiofy-stat-card"><div class="stat-value"><?php echo $stats['clients']; ?></div><div class="stat-label">Active Clients</div></div>
                <div class="studiofy-stat-card"><div class="stat-value"><?php echo $stats['projects']; ?></div><div class="stat-label">Active Projects</div></div>
                <div class="studiofy-stat-card"><div class="stat-value"><?php echo $stats['appts']; ?></div><div class="stat-label">Appointments</div></div>
                <div class="studiofy-stat-card"><div class="stat-value"><?php echo $stats['invoices']; ?></div><div class="stat-label">Pending Invoices</div></div>
            </div>
            <div class="studiofy-panels">
                <div class="studiofy-panel"><h3>Quick Actions</h3>
                    <a href="?page=studiofy-clients" class="button button-primary">New Client</a>
                    <a href="?page=studiofy-invoices&action=create" class="button">New Invoice</a>
                </div>
            </div>
        </div>
        <?php
    }
}
