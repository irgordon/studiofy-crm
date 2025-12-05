<?php
/**
 * Booking Controller
 * @package Studiofy\Admin
 * @version 2.0.4
 */

declare(strict_types=1);

namespace Studiofy\Admin;

class BookingController {

    public function init(): void {
        // Init
    }

    public function render_page(): void {
        ?>
        <div class="wrap studiofy-dark-theme">
            <h1>Appointments <a href="#" class="page-title-action">New Appointment</a></h1>
            
            <div class="studiofy-calendar-view">
                <div class="calendar-header">
                    <button class="button">&laquo; Prev</button>
                    <span><?php echo date('F Y'); ?></span>
                    <button class="button">Next &raquo;</button>
                </div>
                <div class="calendar-grid">
                    <?php 
                    $days = date('t');
                    for($i=1; $i<=$days; $i++) {
                        echo "<div class='calendar-day'><span class='day-num'>$i</span></div>";
                    }
                    ?>
                </div>
            </div>
        </div>
        <style>
            .calendar-grid { 
                display: grid; 
                grid-template-columns: repeat(7, 1fr); 
                gap: 1px; 
                background: #444; 
                margin-top:20px; 
                border: 1px solid #444;
            }
            .calendar-day { 
                background: #1e1e1e; 
                min-height: 100px; 
                padding: 10px; 
                color: #fff; 
            }
            .calendar-day:hover {
                background: #252525;
            }
            .calendar-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #1e1e1e;
                padding: 15px;
                border-radius: 4px 4px 0 0;
            }
        </style>
        <?php
    }
}
