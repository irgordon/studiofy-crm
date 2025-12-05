<?php
declare(strict_types=1);
namespace Studiofy\Utils;
trait TableHelper {
    private function sort_link(string $col_name, string $col_slug, string $default_order = 'asc'): string {
        $current_orderby = $_GET['orderby'] ?? '';
        $current_order = $_GET['order'] ?? 'asc';
        $next_order = ($current_orderby === $col_slug && $current_order === 'asc') ? 'desc' : 'asc';
        $arrow = ($current_orderby === $col_slug) ? (($current_order === 'asc') ? ' &uarr;' : ' &darr;') : '';
        $url = add_query_arg(['orderby' => $col_slug, 'order' => $next_order]);
        return "<a href='".esc_url($url)."'>$col_name $arrow</a>";
    }
}
