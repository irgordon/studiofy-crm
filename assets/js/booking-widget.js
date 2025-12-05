/**
 * Studiofy Scheduler Widget Logic
 * @version 2.0.0
 */
jQuery(document).ready(function($) {
    $('.studiofy-scheduler').each(function() {
        const container = $(this);
        const ApiRoot = studiofySettings.root + 'studiofy/v1/';
        const allowedDays = container.data('days'); // Array of strings e.g. ["1", "2"]
        
        let currentDate = new Date();
        let selectedDate = null;
        let selectedTime = null;

        function renderCalendar(date) {
            const year = date.getFullYear();
            const month = date.getMonth();
            container.find('.current-month-label').text(date.toLocaleString('default', { month: 'long', year: 'numeric' }));

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const grid = container.find('.studiofy-calendar-grid');
            grid.empty();

            // Empty slots for alignment
            for (let i = 0; i < firstDay; i++) {
                grid.append('<div class="studiofy-calendar-day empty"></div>');
            }

            // Days
            for (let d = 1; d <= daysInMonth; d++) {
                const dayDate = new Date(year, month, d);
                const dayOfWeek = dayDate.getDay().toString();
                
                let className = 'studiofy-calendar-day';
                if (!allowedDays.includes(dayOfWeek)) className += ' disabled';
                
                const el = $(`<div class="${className}">${d}</div>`);
                el.data('date', `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`);
                
                if (!className.includes('disabled')) {
                    el.click(function() {
                        container.find('.studiofy-calendar-day').removeClass('selected');
                        $(this).addClass('selected');
                        selectedDate = $(this).data('date');
                        showTimeSlots();
                    });
                }
                grid.append(el);
            }
        }

        function showTimeSlots() {
            const slotContainer = container.find('.slots-container');
            slotContainer.empty();
            container.find('.studiofy-time-slots').removeClass('hidden');

            // Hardcoded Slots for demo (In real app, filter against API availability)
            const times = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00'];
            
            times.forEach(time => {
                const btn = $(`<button type="button" class="studiofy-time-slot">${time}</button>`);
                btn.click(function() {
                    container.find('.studiofy-time-slot').removeClass('selected');
                    $(this).addClass('selected');
                    selectedTime = time;
                    container.find('.studiofy-booking-form').removeClass('hidden');
                });
                slotContainer.append(btn);
            });
        }

        // Navigation
        container.find('.prev-month').click(() => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar(currentDate);
        });
        container.find('.next-month').click(() => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar(currentDate);
        });

        // Submit
        container.find('.studiofy-booking-form').on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('button');
            const name = $(this).find('input[name="name"]').val();
            const email = $(this).find('input[name="email"]').val();

            btn.text('Booking...');

            $.ajax({
                url: ApiRoot + 'bookings',
                method: 'POST',
                beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', studiofySettings.nonce); },
                contentType: 'application/json',
                data: JSON.stringify({
                    date: selectedDate,
                    time: selectedTime,
                    service: container.data('service'),
                    name: name,
                    email: email
                }),
                success: function() {
                    container.html('<div class="studiofy-success-msg">Booking Requested! We will contact you shortly.</div>');
                },
                error: function() {
                    alert('Slot unavailable or error occurred.');
                    btn.text('Confirm Booking');
                }
            });
        });

        // Init
        renderCalendar(currentDate);
    });
});
