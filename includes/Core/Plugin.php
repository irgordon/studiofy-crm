<?php
/**
 * Main Controller
 * @package Studiofy\Core
 * @version 2.0.4
 */

declare(strict_types=1);

namespace Studiofy\Core;

use Studiofy\Admin\Menu;
use Studiofy\Admin\GalleryController;
use Studiofy\Api\ClientRoutes;
use Studiofy\Api\BookingRoutes;
use Studiofy\Api\KanbanRoutes;
use Studiofy\Api\ProjectEndpoints;
use Studiofy\Api\GalleryRoutes;
use Studiofy\Api\InvoiceRoutes;

class Plugin {
    
    public function run(): void {
        // Admin Menu & UI
        (new Menu())->init();
        
        // Modules
        (new GalleryController())->init();
        
        // REST APIs
        (new ClientRoutes())->init();
        (new BookingRoutes())->init();
        (new KanbanRoutes())->init();
        (new ProjectEndpoints())->init();
        (new GalleryRoutes())->init();
        (new InvoiceRoutes())->init();
    }
}
