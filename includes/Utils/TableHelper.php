<?php
/**
 * Table Helper
 * @package Studiofy\Utils
 * @version 2.2.14
 */

declare(strict_types=1);

namespace Studiofy\Utils;

trait TableHelper {
    private function sort_link(string $col_name, string $col_slug, string $default_order = 'asc'): string {
        $current_orderby = $_GET['orderby'] ?? '';
        $current_order   = $_GET['order'] ?? 'asc';
        $next_order = ($current_orderby === $col_slug && $current_order === 'asc') ? 'desc' : 'asc';
        
        $arrow = '';
        $aria_sort = 'none';

        if ($current_orderby === $col_slug) {
            $arrow = ($current_order === 'asc') ? ' &uarr;' : ' &darr;';
            $aria_sort = ($current_order === 'asc') ? 'ascending' : 'descending';
        }

        $url = add_query_arg(['orderby' => $col_slug, 'order' => $next_order]);
        
        return "<a href='" . esc_url($url) . "' aria-label='Sort by $col_name' aria-sort='$aria_sort'><span>$col_name</span> $arrow</a>";
    }
}
