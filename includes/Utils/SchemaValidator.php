<?php
/**
 * Schema Validator
 * @package Studiofy\Utils
 * @version 2.0.4
 */

declare(strict_types=1);

namespace Studiofy\Utils;

class SchemaValidator {
    public static function validate(array $data, array $schema): array {
        $clean_data = [];

        foreach ($schema as $field => $type) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            switch ($type) {
                case 'int':
                    $clean_data[$field] = (int) $value;
                    break;
                case 'float':
                    $clean_data[$field] = (float) $value;
                    break;
                case 'string':
                    $clean_data[$field] = sanitize_text_field((string) $value);
                    break;
                case 'email':
                    $clean_data[$field] = sanitize_email((string) $value);
                    break;
                case 'date':
                    $d = \DateTime::createFromFormat('Y-m-d', $value);
                    $clean_data[$field] = ($d && $d->format('Y-m-d') === $value) ? $value : date('Y-m-d');
                    break;
                default:
                    $clean_data[$field] = sanitize_text_field((string) $value);
            }
        }

        return $clean_data;
    }
}
