<?php
class Studiofy_Deactivator {
    public static function deactivate() {
        // Clear crons
        wp_clear_scheduled_hook( 'studiofy_async_generate_invoice' );
        flush_rewrite_rules();
    }
}
