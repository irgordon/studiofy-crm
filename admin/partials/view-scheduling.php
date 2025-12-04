<div class="wrap">
    <h1>Scheduling</h1>
    <p>Manage appointments via Google Calendar.</p>
    
    <?php
    require_once STUDIOFY_PATH . 'includes/integrations/class-studiofy-google-calendar.php';
    $gcal = new Studiofy_Google_Calendar();
    
    if(!$gcal->is_connected()) {
        echo '<div class="notice notice-warning"><p>Google Calendar is not connected. <a href="admin.php?page=studiofy-settings">Configure here</a>.</p></div>';
    } else {
        echo '<a href="#" class="button button-primary">Import from Google Calendar</a>';
        echo '<hr>';
        // Here you would loop through fetched events
        echo '<p><em>Calendar integration active. Events will appear here.</em></p>';
    }
    ?>
</div>
