<?php
/**
 * Main Controller
 * @package Studiofy\Core
 * @version 2.2.56
 */

declare(strict_types=1);

namespace Studiofy\Core;

use Studiofy\Admin\Menu;
use Studiofy\Admin\GalleryController;
use Studiofy\Api\CustomerRoutes;
use Studiofy\Api\BookingRoutes;
use Studiofy\Api\KanbanRoutes;
use Studiofy\Api\ProjectEndpoints;
use Studiofy\Api\GalleryRoutes;
use Studiofy\Api\InvoiceRoutes;
use Studiofy\Core\DemoDataManager;
use Studiofy\Frontend\GalleryShortcode; 
use Studiofy\Frontend\ContractShortcode;
use Studiofy\Frontend\PaymentShortcode; // NEW

class Plugin {
    
    public function run(): void {
        // Admin UI
        (new Menu())->init();
        
        // Core Logic
        (new DemoDataManager())->init();

        // Frontend Logic 
        (new GalleryShortcode())->init();
        (new ContractShortcode())->init();
        (new PaymentShortcode())->init(); // NEW

        // Modules
        (new GalleryController())->init();
        
        // REST APIs
        (new CustomerRoutes())->init();
        (new BookingRoutes())->init();
        (new KanbanRoutes())->init();
        (new ProjectEndpoints())->init();
        (new GalleryRoutes())->init();
        (new InvoiceRoutes())->init();
    }
}
