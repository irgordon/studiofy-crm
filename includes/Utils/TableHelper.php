<?php
/**
 * Table Helper
 * @package Studiofy\Utils
 * @version 2.0.0
 */
declare(strict_types=1);
namespace Studiofy\Utils;
trait TableHelper {
    private function sort_link(string $n, string $s): string { return "<a href='?orderby=$s'>$n</a>"; }
}
