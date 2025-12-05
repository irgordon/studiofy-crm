<?php
/**
 * Encryption Engine
 * @package Studiofy\Security
 * @version 2.0.4
 */

declare(strict_types=1);

namespace Studiofy\Security;

class Encryption {
    private const CIPHER = 'AES-256-CBC';

    private function get_key(): string {
        return defined('STUDIOFY_KEY') ? STUDIOFY_KEY : 'fallback_key_32_bytes_long_string!!';
    }

    public function encrypt(string $data): string {
        if (empty($data)) return '';
        $ivlen = openssl_cipher_iv_length(self::CIPHER);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $encrypted = openssl_encrypt($data, self::CIPHER, $this->get_key(), 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $data): string {
        if (empty($data)) return '';
        $data = base64_decode($data);
        $ivlen = openssl_cipher_iv_length(self::CIPHER);
        if (strlen($data) < $ivlen) return '';
        $iv = substr($data, 0, $ivlen);
        $encrypted = substr($data, $ivlen);
        return openssl_decrypt($encrypted, self::CIPHER, $this->get_key(), 0, $iv) ?: '';
    }
}
