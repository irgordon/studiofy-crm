<?php
/**
 * Deactivator
 * @package Studiofy\Core
 * @version 2.2.22
 */

declare(strict_types=1);

namespace Studiofy\Core;

class Deactivator {
    public static function deactivate(): void {
        // Deactivation should NOT delete data (WordPress Best Practice).
        // Data deletion is now handled exclusively in uninstall.php
        flush_rewrite_rules();
    }
}
