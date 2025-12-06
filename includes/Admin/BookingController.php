<?php
/**
 * Booking Controller
 * @package Studiofy\Admin
 * @version 2.1.9
 */

declare(strict_types=1);

namespace Studiofy\Admin;

class BookingController {

    public function init(): void {
        add_action('admin_post_studiofy_save_booking', [$this, 'handle_save']);
        add_action('admin_post_studiofy_delete_booking', [$this, 'handle_delete']);
    }

    public function render_page(): void {
        global $wpdb;
        
        $current_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
        $current_year  = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        
        $next_month = $current_month + 1;
        $next_year = $current_year;
        if ($next_month > 12) { $next_month = 1; $next_year++; }
        
        $prev_month = $current_month - 1;
        $prev_year = $current_year;
        if ($prev_month < 1) { $prev_month = 12; $prev_year--; }

        $month_name = date('F Y', mktime(0, 0, 0, $current_month, 1, $current_year));
        $customers = $wpdb->get_results("SELECT id, first_name, last_name FROM {$wpdb->prefix}studiofy_customers ORDER BY last_name ASC");

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Appointments</h1>
            <button id="btn-new-appt" class="page-title-action">New Appointment</button>
            <hr class="wp-header-end">
            
            <div class="studiofy-calendar-view">
                <div class="calendar-header">
                    <a href="?page=studiofy-appointments&month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="button">&laquo; Back</a>
                    <span style="font-size:18px; font-weight:bold; margin:0 20px;"><?php echo $month_name; ?></span>
                    <a href="?page=studiofy-appointments&month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="button">Next &raquo;</a>
                </div>
                
                <div class="studiofy-cal-grid">
                    <div class="cal-head">Sun</div>
                    <div class="cal-head">Mon</div>
                    <div class="cal-head">Tue</div>
                    <div class="cal-head">Wed</div>
                    <div class="cal-head">Thu</div>
                    <div class="cal-head">Fri</div>
                    <div class="cal-head">Sat</div>

                    <?php 
                    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
                    $first_day_idx = date('w', mktime(0, 0, 0, $current_month, 1, $current_year));
                    
                    for($i=0; $i<$first_day_idx; $i++) echo "<div class='cal-day empty'></div>";
                    
                    for($i=1; $i<=$days_in_month; $i++) {
                        $date_str = "$current_year-" . str_pad((string)$current_month, 2, '0', STR_PAD_LEFT) . "-" . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
                        $bookings = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}studiofy_bookings WHERE booking_date = %s", $date_str));
                        
                        echo "<div class='cal-day'><span class='day-num'>$i</span>";
                        foreach($bookings as $b) {
                            $time_display = date('g:ia', strtotime($b->booking_time));
                            $delete_url = wp_nonce_url(admin_url('admin-post.php?action=studiofy_delete_booking&id=' . $b->id), 'delete_booking_' . $b->id);
                            
                            $event_data = htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8');
                            
                            echo "<div class='calendar-event' data-booking='$event_data'>";
                            echo "<span><small>{$time_display}</small> " . esc_html($b->title) . "</span>";
                            echo "<a href='$delete_url' class='delete-event' onclick='event.stopPropagation(); return confirm(\"Delete?\");'>&times;</a>";
                            echo "</div>";
                        }
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <div id="modal-new-appt" class="studiofy-modal-overlay studiofy-hidden" style="display:none;">
            <div class="studiofy-modal">
                <div class="studiofy-modal-header">
                    <h2>Appointment Details</h2>
                    <button class="close-modal">&times;</button>
                </div>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="studiofy-modal-body" id="booking-form">
                    <input type="hidden" name="action" value="studiofy_save_booking">
                    <input type="hidden" name="id" id="booking_id" value="">
                    <?php wp_nonce_field('save_booking', 'studiofy_nonce'); ?>
                    
                    <div class="studiofy-form-row">
                        <div class="studiofy-col"><label>Title *</label><input type="text" name="title" id="booking_title" required class="widefat"></div>
                    </div>
                    <div class="studiofy-form-row">
                        <div class="studiofy-col"><label>Customer *</label>
                            <select name="customer_id" id="booking_customer" required class="widefat">
                                <option value="">Select a customer</option>
                                <?php foreach($customers as $c) echo "<option value='{$c->id}'>{$c->first_name} {$c->last_name}</option>"; ?>
                            </select>
                        </div>
                    </div>
                    <div class="studiofy-form-row">
                        <div class="studiofy-col"><label>Date</label><input type="date" name="start_date" id="booking_date" required class="widefat"></div>
                        <div class="studiofy-col"><label>Time</label><input type="time" name="start_time" id="booking_time" required class="widefat"></div>
                    </div>
                    <div class="studiofy-form-row">
                        <div class="studiofy-col"><label>Status</label>
                            <select name="status" id="booking_status" class="widefat">
                                <option value="Scheduled">Scheduled</option><option value="Completed">Completed</option><option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="studiofy-form-actions">
                        <button type="button" class="button close-modal">Cancel</button> 
                        <button type="submit" class="button button-primary" id="btn-save-booking">Save</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    public function handle_save(): void {
        if (!isset($_POST['studiofy_nonce']) || !wp_verify_nonce($_POST['studiofy_nonce'], 'save_booking')) wp_die('Security check failed');
        global $wpdb;
        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'customer_id' => (int)$_POST['customer_id'],
            'booking_date' => sanitize_text_field($_POST['start_date']),
            'booking_time' => sanitize_text_field($_POST['start_time']),
            'status' => sanitize_text_field($_POST['status'])
        ];
        if (!empty($_POST['id'])) $wpdb->update($wpdb->prefix.'studiofy_bookings', $data, ['id'=>(int)$_POST['id']]);
        else $wpdb->insert($wpdb->prefix.'studiofy_bookings', $data);
        wp_redirect(admin_url('admin.php?page=studiofy-appointments')); exit;
    }

    public function handle_delete(): void {
        check_admin_referer('delete_booking_' . $_GET['id']);
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'studiofy_bookings', ['id' => (int) $_GET['id']]);
        wp_redirect(admin_url('admin.php?page=studiofy-appointments')); exit;
    }
}
