<?php
/**
 * Deactivator
 * @package Studiofy\Core
 * @version 2.0.4
 */

declare(strict_types=1);

namespace Studiofy\Core;

class Deactivator {
    public static function deactivate(): void {
        flush_rewrite_rules();
    }
}
