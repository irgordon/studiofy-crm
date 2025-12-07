<?php
/**
 * Dashboard Controller
 * @package Studiofy\Admin
 * @version 2.2.50
 */

declare(strict_types=1);

namespace Studiofy\Admin;

class DashboardController {

    public function render_page(): void {
        global $wpdb;
        
        // Cache Stats for performance (60 seconds)
        $stats = get_transient('studiofy_dashboard_stats');
        
        if (false === $stats) {
            $stats = [
                'customers' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_customers"),
                'projects'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_projects"),
                'appts'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_bookings WHERE status='Scheduled'"),
                'invoices'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_invoices WHERE status='Draft'"),
                'revenue'   => (float) $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}studiofy_invoices WHERE status='Paid'")
            ];
            
            set_transient('studiofy_dashboard_stats', $stats, 60); 
        }
        
        ?>
        <div class="wrap studiofy-dark-theme">
            <div class="studiofy-dashboard-header">
                <div class="header-content">
                    <h1>Welcome to Studiofy CRM</h1>
                    <p class="welcome-text">Thank you for choosing Studiofy to manage your photography business. This dashboard provides a quick snapshot of your current active projects, invoices, and contracts, helping you stay organized and focused on creating art.</p>
                    <a href="https://github.com/irgordon/studiofy-crm" target="_blank" class="button button-secondary">Visit Studiofy CRM Website</a>
                </div>
                <div class="header-logo">
                    <svg width="150" height="120" viewBox="0 0 500 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="lens_gradient_dash" x1="200" y1="130" x2="300" y2="230" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#4f94d4"/> <stop offset="1" stop-color="#2271b1"/> 
                            </linearGradient>
                        </defs>
                        <g id="Camera_Icon">
                            <rect x="100" y="80" width="300" height="200" rx="20" fill="black"/>
                            <path d="M180 80 L210 40 H290 L320 80 H180 Z" fill="black"/>
                            <rect x="120" y="70" width="40" height="10" rx="2" fill="black"/>
                            <circle cx="250" cy="180" r="85" fill="white"/> <circle cx="250" cy="180" r="75" fill="black"/> <circle cx="250" cy="180" r="60" fill="url(#lens_gradient_dash)"/>
                            <ellipse cx="270" cy="160" rx="20" ry="12" transform="rotate(-45 270 160)" fill="white" fill-opacity="0.4"/>
                            <circle cx="230" cy="200" r="5" fill="white" fill-opacity="0.2"/>
                            <rect x="115" y="100" width="15" height="160" rx="5" fill="#333333"/>
                        </g>
                        <g id="Typography">
                            <text x="250" y="340" font-family="Arial, Helvetica, sans-serif" font-size="60" text-anchor="middle" fill="black">
                                <tspan font-weight="900" letter-spacing="2">STUDIOFY</tspan> 
                                <tspan font-weight="400" letter-spacing="4"> CRM</tspan>
                            </text>
                        </g>
                    </svg>
                </div>
            </div>
            
            <div class="studiofy-dashboard-grid">
                <div class="studiofy-stat-card">
                    <div class="stat-icon-wrapper"><span class="dashicons dashicons-admin-users"></span></div>
                    <div class="stat-content">
                        <div class="stat-label">Total Customers</div>
                        <div class="stat-value"><?php echo esc_html($stats['customers']); ?></div>
                    </div>
                </div>
                <div class="studiofy-stat-card">
                    <div class="stat-icon-wrapper"><span class="dashicons dashicons-portfolio"></span></div>
                    <div class="stat-content">
                        <div class="stat-label">Total Projects</div>
                        <div class="stat-value"><?php echo esc_html($stats['projects']); ?></div>
                    </div>
                </div>
                <div class="studiofy-stat-card">
                    <div class="stat-icon-wrapper"><span class="dashicons dashicons-calendar-alt"></span></div>
                    <div class="stat-content">
                        <div class="stat-label">Upcoming Appointments</div>
                        <div class="stat-value"><?php echo esc_html($stats['appts']); ?></div>
                    </div>
                </div>
                <div class="studiofy-stat-card">
                    <div class="stat-icon-wrapper"><span class="dashicons dashicons-media-spreadsheet"></span></div>
                    <div class="stat-content">
                        <div class="stat-label">Pending Invoices</div>
                        <div class="stat-value"><?php echo esc_html($stats['invoices']); ?></div>
                    </div>
                </div>
            </div>

            <div class="studiofy-dashboard-panels">
                <div class="postbox">
                    <h2 class="hndle">Revenue Overview</h2>
                    <div class="inside">
                        <p style="font-size: 32px; font-weight: bold; margin: 0; color:#46b450;">
                            <?php echo '$' . number_format((float)$stats['revenue'], 2); ?>
                        </p>
                        <p class="description">Total Revenue (Paid Invoices)</p>
                    </div>
                </div>
                <div class="postbox">
                    <h2 class="hndle">Quick Actions</h2>
                    <div class="inside action-buttons">
                        <button class="button button-primary" onclick="location.href='?page=studiofy-customers&action=new'">+ New Customer</button>
                        <button class="button" onclick="location.href='?page=studiofy-projects&action=new'">+ New Project</button>
                        <button class="button" onclick="location.href='?page=studiofy-invoices&action=create'">+ New Invoice</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
