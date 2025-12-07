<?php
/**
 * Booking Controller
 * @package Studiofy\Admin
 * @version 2.2.52
 */

declare(strict_types=1);

namespace Studiofy\Admin;

use Studiofy\Utils\TableHelper;

class BookingController {
    use TableHelper;

    public function init(): void {
        add_action('admin_post_studiofy_save_booking', [$this, 'handle_save']);
        add_action('admin_post_studiofy_delete_booking', [$this, 'handle_delete']); // NEW Action
    }

    public function render_page(): void {
        global $wpdb;
        $customers = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_customers ORDER BY last_name ASC");
        
        $current_month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('Y-m');
        $start_date = date('Y-m-01', strtotime($current_month));
        $end_date = date('Y-m-t', strtotime($current_month));
        
        // Fetch appointments for this month
        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}studiofy_bookings 
             WHERE booking_date BETWEEN %s AND %s ORDER BY booking_time ASC",
            $start_date, $end_date
        ));

        // Map events to days
        $calendar = [];
        foreach ($events as $e) {
            $day = (int)date('j', strtotime($e->booking_date));
            $calendar[$day][] = $e;
        }
        
        $prev_month = date('Y-m', strtotime($current_month . ' -1 month'));
        $next_month = date('Y-m', strtotime($current_month . ' +1 month'));

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Appointments</h1>
            <button id="btn-new-appt" class="page-title-action">New Appointment</button>
            <hr class="wp-header-end">
            
            <div class="studiofy-calendar-view">
                <div class="calendar-header">
                    <a href="?page=studiofy-appointments&month=<?php echo $prev_month; ?>" class="button">&laquo; Prev</a>
                    <h2><?php echo date('F Y', strtotime($current_month)); ?></h2>
                    <a href="?page=studiofy-appointments&month=<?php echo $next_month; ?>" class="button">Next &raquo;</a>
                </div>
                
                <div class="studiofy-cal-grid">
                    <?php 
                    $days_in_month = date('t', strtotime($current_month));
                    $first_day_idx = date('w', strtotime($start_date)); // 0 (Sun) - 6 (Sat)
                    
                    // Header Row
                    $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    foreach($weekdays as $d) echo "<div class='cal-head'>$d</div>";
                    
                    // Empty cells before 1st
                    for($i=0; $i<$first_day_idx; $i++) echo "<div class='cal-day empty'></div>";
                    
                    // Days
                    for($day=1; $day<=$days_in_month; $day++) {
                        echo "<div class='cal-day'><span class='day-num'>$day</span>";
                        if(isset($calendar[$day])) {
                            foreach($calendar[$day] as $appt) {
                                $time = date('g:ia', strtotime($appt->booking_time));
                                $del_url = wp_nonce_url(admin_url('admin-post.php?action=studiofy_delete_booking&id=' . $appt->id), 'delete_booking_' . $appt->id);
                                
                                echo "<div class='calendar-event' title='" . esc_attr($appt->title . ' - ' . $appt->location) . "'>";
                                echo "<span>{$time} " . esc_html($appt->title) . "</span>";
                                echo "<a href='" . $del_url . "' class='delete-event' onclick='return confirm(\"Delete this appointment?\");'>&times;</a>";
                                echo "</div>";
                            }
                        }
                        echo "</div>";
                    }
                    
                    // Fill remaining grid
                    $total_cells = $first_day_idx + $days_in_month;
                    $rows = ceil($total_cells / 7);
                    $remaining = ($rows * 7) - $total_cells;
                    for($i=0; $i<$remaining; $i++) echo "<div class='cal-day empty'></div>";
                    ?>
                </div>
            </div>
        </div>

        <div id="modal-new-appt" class="studiofy-modal-overlay studiofy-hidden">
            <div class="studiofy-modal">
                <div class="studiofy-modal-header">
                    <h2>New Appointment</h2>
                    <button class="close-modal">&times;</button>
                </div>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="studiofy-modal-body">
                    <input type="hidden" name="action" value="studiofy_save_booking">
                    <?php wp_nonce_field('save_booking', 'studiofy_nonce'); ?>
                    
                    <div class="studiofy-form-row">
                        <div class="studiofy-col"><label>Title</label><input type="text" name="title" required class="widefat"></div>
                    </div>
                    <div class="studiofy-form-row">
                        <div class="studiofy-col"><label>Date</label><input type="date" name="booking_date" required class="widefat"></div>
                        <div class="studiofy-col"><label>Time</label><input type="time" name="booking_time" required class="widefat"></div>
                    </div>
                    <div class="studiofy-form-row">
                        <div class="studiofy-col"><label>Customer</label>
                            <select name="customer_id" class="widefat">
                                <option value="">-- Guest --</option>
                                <?php foreach($customers as $c) echo "<option value='{$c->id}'>{$c->first_name} {$c->last_name}</option>"; ?>
                            </select>
                        </div>
                    </div>
                    <div class="studiofy-form-row">
                        <div class="studiofy-col"><label>Location</label><input type="text" name="location" class="widefat"></div>
                    </div>
                    <div class="studiofy-form-row">
                        <div class="studiofy-col"><label>Notes</label><textarea name="notes" class="widefat" rows="2"></textarea></div>
                    </div>
                    
                    <div class="studiofy-form-actions">
                        <button type="button" class="button close-modal">Cancel</button>
                        <button type="submit" class="button button-primary">Save Appointment</button>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
            jQuery(document).ready(function($){
                $('#btn-new-appt').click(function(){ $('#modal-new-appt').removeClass('studiofy-hidden'); });
                $('.close-modal').click(function(){ $(this).closest('.studiofy-modal-overlay').addClass('studiofy-hidden'); });
            });
        </script>
        <?php
    }

    public function handle_save(): void {
        check_admin_referer('save_booking', 'studiofy_nonce');
        global $wpdb;
        
        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'booking_date' => sanitize_text_field($_POST['booking_date']),
            'booking_time' => sanitize_text_field($_POST['booking_time']),
            'customer_id' => !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null,
            'location' => sanitize_text_field($_POST['location']),
            'notes' => sanitize_textarea_field($_POST['notes']),
            'status' => 'Scheduled'
        ];
        
        $wpdb->insert($wpdb->prefix.'studiofy_bookings', array_merge($data, ['created_at' => current_time('mysql')]));
        wp_redirect(admin_url('admin.php?page=studiofy-appointments'));
        exit;
    }

    public function handle_delete(): void {
        if (!isset($_GET['id'])) wp_die('Missing ID');
        check_admin_referer('delete_booking_' . $_GET['id']);
        
        global $wpdb;
        $id = (int)$_GET['id'];
        
        $wpdb->delete($wpdb->prefix . 'studiofy_bookings', ['id' => $id]);
        
        wp_redirect(admin_url('admin.php?page=studiofy-appointments&msg=deleted'));
        exit;
    }
}
