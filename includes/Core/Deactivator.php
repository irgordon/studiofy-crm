<?php
/**
 * Deactivator
 * @package Studiofy\Core
 * @version 2.2.9
 */

declare(strict_types=1);

namespace Studiofy\Core;

class Deactivator {
    
    public static function deactivate(): void {
        // Check if Demo Data exists
        if (get_option('studiofy_demo_data_ids')) {
            // Instantiate Manager to clean up
            $demoManager = new DemoDataManager();
            $demoManager->delete_demo_data();
        }

        flush_rewrite_rules();
    }
}
