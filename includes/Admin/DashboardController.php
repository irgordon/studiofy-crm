<?php
/**
 * Dashboard Controller
 * @package Studiofy\Admin
 * @version 2.2.56
 */

declare(strict_types=1);

namespace Studiofy\Admin;

class DashboardController {

    public function render_page(): void {
        global $wpdb;
        
        // --- 1. Main Stats (Optimized Single Query) ---
        $stats = get_transient('studiofy_dashboard_stats');
        
        if (false === $stats) {
            // Combine counts into one query for performance
            $sql = "SELECT 
                (SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_customers) as customers,
                (SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_projects) as projects,
                (SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_bookings WHERE status='Scheduled') as appts,
                (SELECT COUNT(*) FROM {$wpdb->prefix}studiofy_invoices WHERE status='Draft') as invoices,
                (SELECT SUM(amount) FROM {$wpdb->prefix}studiofy_invoices WHERE status='Paid') as revenue";
            
            $result = $wpdb->get_row($sql);
            
            $stats = [
                'customers' => (int) ($result->customers ?? 0),
                'projects'  => (int) ($result->projects ?? 0),
                'appts'     => (int) ($result->appts ?? 0),
                'invoices'  => (int) ($result->invoices ?? 0),
                'revenue'   => (float) ($result->revenue ?? 0.00)
            ];
            
            // Cache for 60 seconds
            set_transient('studiofy_dashboard_stats', $stats, 60); 
        }
        
        // --- 2. Proofing Feed (Cached) ---
        $feed_items = get_transient('studiofy_dashboard_feed');
        
        if (false === $feed_items) {
            $selections = $wpdb->get_results("
                SELECT s.*, g.title as gallery_title, p.title as project_title, f.file_name, f.created_at as file_created, s.gallery_id 
                FROM {$wpdb->prefix}studiofy_gallery_selections s 
                JOIN {$wpdb->prefix}studiofy_galleries g ON s.gallery_id = g.id 
                LEFT JOIN {$wpdb->prefix}studiofy_projects p ON (SELECT id FROM {$wpdb->prefix}studiofy_projects WHERE customer_id = g.customer_id LIMIT 1) = p.id
                JOIN {$wpdb->prefix}studiofy_gallery_files f ON s.attachment_id = f.id
                WHERE s.status = 'approved'
                ORDER BY s.created_at DESC LIMIT 10
            ");

            $feed_items = [];
            if (!empty($selections)) {
                // Batch fetch file lists for ID calculation to avoid N+1 queries
                $gallery_ids = array_unique(array_column($selections, 'gallery_id'));
                $gallery_file_maps = [];
                
                if (!empty($gallery_ids)) {
                    $g_in = implode(',', array_map('intval', $gallery_ids));
                    $all_files_raw = $wpdb->get_results("SELECT id, gallery_id FROM {$wpdb->prefix}studiofy_gallery_files WHERE gallery_id IN ($g_in) ORDER BY created_at DESC");
                    
                    foreach ($all_files_raw as $f) {
                        $gallery_file_maps[$f->gallery_id][] = $f->id;
                    }
                }

                foreach($selections as $sel) {
                    $all_files = $gallery_file_maps[$sel->gallery_id] ?? [];
                    $index = array_search($sel->attachment_id, $all_files);
                    $visual_id = ($index !== false) ? '#' . sprintf('%04d', 100 + $index) : '#????';
                    
                    $feed_items[] = (object) [
                        'project' => $sel->project_title ?: 'Unassigned Project',
                        'gallery' => $sel->gallery_title,
                        'image_id' => $visual_id,
                        'date' => date('M j, g:ia', strtotime($sel->created_at))
                    ];
                }
            }
            // Cache feed for 5 minutes
            set_transient('studiofy_dashboard_feed', $feed_items, 300);
        }
        
        ?>
        <div class="wrap studiofy-dark-theme">
            <div class="studiofy-dashboard-header">
                <div class="header-content">
                    <h1>Welcome to Studiofy CRM</h1>
                    <p class="welcome-text">Thank you for choosing Studiofy to manage your photography business. This dashboard provides a quick snapshot of your current active projects, invoices, and contracts.</p>
                    <a href="https://github.com/irgordon/studiofy-crm" target="_blank" class="button button-secondary">Visit Studiofy CRM Website</a>
                </div>
                <div class="header-logo">
                    <svg width="150" height="120" viewBox="0 0 500 400" fill="none" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="lens_gradient_dash" x1="200" y1="130" x2="300" y2="230" gradientUnits="userSpaceOnUse"><stop stop-color="#4f94d4"/><stop offset="1" stop-color="#2271b1"/></linearGradient></defs><g id="Camera_Icon"><rect x="100" y="80" width="300" height="200" rx="20" fill="black"/><path d="M180 80 L210 40 H290 L320 80 H180 Z" fill="black"/><rect x="120" y="70" width="40" height="10" rx="2" fill="black"/><circle cx="250" cy="180" r="85" fill="white"/><circle cx="250" cy="180" r="75" fill="black"/><circle cx="250" cy="180" r="60" fill="url(#lens_gradient_dash)"/><ellipse cx="270" cy="160" rx="20" ry="12" transform="rotate(-45 270 160)" fill="white" fill-opacity="0.4"/><circle cx="230" cy="200" r="5" fill="white" fill-opacity="0.2"/><rect x="115" y="100" width="15" height="160" rx="5" fill="#333333"/></g><g id="Typography"><text x="250" y="340" font-family="Arial, Helvetica, sans-serif" font-size="60" text-anchor="middle" fill="black"><tspan font-weight="900" letter-spacing="2">STUDIOFY</tspan> <tspan font-weight="400" letter-spacing="4"> CRM</tspan></text></g></svg>
                </div>
            </div>
            
            <div class="studiofy-dashboard-grid">
                <div class="studiofy-stat-card"><div class="stat-icon-wrapper"><span class="dashicons dashicons-admin-users"></span></div><div class="stat-content"><div class="stat-label">Total Customers</div><div class="stat-value"><?php echo esc_html($stats['customers']); ?></div></div></div>
                <div class="studiofy-stat-card"><div class="stat-icon-wrapper"><span class="dashicons dashicons-portfolio"></span></div><div class="stat-content"><div class="stat-label">Total Projects</div><div class="stat-value"><?php echo esc_html($stats['projects']); ?></div></div></div>
                <div class="studiofy-stat-card"><div class="stat-icon-wrapper"><span class="dashicons dashicons-calendar-alt"></span></div><div class="stat-content"><div class="stat-label">Upcoming Appointments</div><div class="stat-value"><?php echo esc_html($stats['appts']); ?></div></div></div>
                <div class="studiofy-stat-card"><div class="stat-icon-wrapper"><span class="dashicons dashicons-media-spreadsheet"></span></div><div class="stat-content"><div class="stat-label">Pending Invoices</div><div class="stat-value"><?php echo esc_html($stats['invoices']); ?></div></div></div>
            </div>

            <div class="studiofy-dashboard-layout" style="display:flex; gap:20px; flex-wrap:wrap;">
                <div class="studiofy-dash-col" style="flex:2; min-width:300px;">
                    <div class="postbox">
                        <h2 class="hndle">Recent Proofing Activity</h2>
                        <div class="inside" style="padding:0;">
                            <?php if(empty($feed_items)): ?>
                                <p style="padding:15px;">No recent selections.</p>
                            <?php else: ?>
                                <table class="wp-list-table widefat striped" style="box-shadow:none; border:none;">
                                    <thead><tr><th>Project</th><th>Image ID</th><th>Time</th></tr></thead>
                                    <tbody>
                                        <?php foreach($feed_items as $item): ?>
                                            <tr>
                                                <td><strong><?php echo esc_html($item->project); ?></strong><br><span style="color:#777; font-size:11px;"><?php echo esc_html($item->gallery); ?></span></td>
                                                <td><span class="studiofy-badge active"><?php echo esc_html($item->image_id); ?></span></td>
                                                <td style="color:#666;"><?php echo esc_html($item->date); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="studiofy-dash-col" style="flex:1; min-width:250px;">
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
                            <button class="button button-primary" style="width:100%; margin-bottom:5px;" onclick="location.href='?page=studiofy-customers&action=new'">+ New Customer</button>
                            <button class="button" style="width:100%; margin-bottom:5px;" onclick="location.href='?page=studiofy-projects&action=new'">+ New Project</button>
                            <button class="button" style="width:100%;" onclick="location.href='?page=studiofy-invoices&action=create'">+ New Invoice</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
