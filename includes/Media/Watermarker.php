<?php
/**
 * Watermarker
 * @package Studiofy\Media
 * @version 2.0.4
 */

declare(strict_types=1);

namespace Studiofy\Media;

class Watermarker {
    public function apply_watermark(int $attachment_id): string|false {
        $upload_dir = wp_upload_dir();
        $original_file = get_attached_file($attachment_id);
        if(!file_exists($original_file)) return false;

        $file_info = pathinfo($original_file);
        $safe_filename = sanitize_file_name($file_info['filename'] . '-proof.' . $file_info['extension']);
        $wm_path = $file_info['dirname'] . '/' . $safe_filename;
        $wm_url = $upload_dir['url'] . '/' . $safe_filename;

        if (file_exists($wm_path)) return $wm_url;

        $image = wp_get_image_editor($original_file);
        if (!is_wp_error($image)) {
            $image->resize(1200, 1200, false);
            // Future: Add composite watermark logic here
            $image->save($wm_path);
            return $wm_url;
        }
        return '';
    }
}
