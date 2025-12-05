<?php
/**
 * Dashboard Controller
 * @package Studiofy\Admin
 * @version 2.0.7
 */

declare(strict_types=1);

namespace Studiofy\Admin;

class DashboardController {

    public function render_page(): void {
        global $wpdb;
        
        $stats = get_transient('studiofy_dashboard_stats');
        
        if (false === $stats) {
            $stats = [
                'customers' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_customers WHERE status='Active'"),
                'projects'  => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_projects WHERE status='in_progress'"),
                'appts'     => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_bookings WHERE status='Scheduled'"),
                'invoices'  => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_invoices WHERE status='Draft'")
            ];
            set_transient('studiofy_dashboard_stats', $stats, 3600); 
        }
        
        ?>
        <div class="wrap">
            <h1>Dashboard</h1>
            <p>Welcome back! Here's what's happening with your business.</p>
            
            <div class="studiofy-dashboard-grid">
                <div class="studiofy-stat-card">
                    <div class="stat-icon-wrapper"><span class="dashicons dashicons-admin-users"></span></div>
                    <div class="stat-label">Active Customers</div>
                    <div class="stat-value"><?php echo esc_html($stats['customers']); ?></div>
                </div>
                <div class="studiofy-stat-card">
                    <div class="stat-icon-wrapper"><span class="dashicons dashicons-portfolio"></span></div>
                    <div class="stat-label">Active Projects</div>
                    <div class="stat-value"><?php echo esc_html($stats['projects']); ?></div>
                </div>
                <div class="studiofy-stat-card">
                    <div class="stat-icon-wrapper"><span class="dashicons dashicons-calendar-alt"></span></div>
                    <div class="stat-label">Appointments</div>
                    <div class="stat-value"><?php echo esc_html($stats['appts']); ?></div>
                </div>
                <div class="studiofy-stat-card">
                    <div class="stat-icon-wrapper"><span class="dashicons dashicons-media-spreadsheet"></span></div>
                    <div class="stat-label">Pending Invoices</div>
                    <div class="stat-value"><?php echo esc_html($stats['invoices']); ?></div>
                </div>
            </div>

            <div class="studiofy-dashboard-panels">
                <div class="postbox">
                    <h2 class="hndle">Revenue Overview</h2>
                    <div class="inside">
                        <p style="font-size: 24px; font-weight: bold; margin: 0;">$0.00</p>
                        <p class="description">Total Revenue (Paid Invoices)</p>
                    </div>
                </div>
                <div class="postbox">
                    <h2 class="hndle">Quick Actions</h2>
                    <div class="inside action-buttons">
                        <button class="button button-primary" onclick="location.href='?page=studiofy-customers'">+ New Customer</button>
                        <button class="button" onclick="location.href='?page=studiofy-projects'">+ New Project</button>
                        <button class="button" onclick="location.href='?page=studiofy-invoices&action=create'">+ New Invoice</button>
                        <button class="button" onclick="location.href='?page=studiofy-contracts&action=create'">+ New Contract</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
