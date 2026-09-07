<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Minimal WordPress stubs for unit tests that do not boot WordPress.
 */
if (! function_exists('wp_unslash')) {
    /**
     * Strip slashes from a string or nested array of strings.
     *
     * @param mixed $value Value to unslash.
     * @return mixed
     */
    function wp_unslash($value)
    {
        if (is_array($value)) {
            return array_map('wp_unslash', $value);
        }

        return is_string($value) ? stripslashes($value) : $value;
    }
}

if (! function_exists('sanitize_key')) {
    /**
     * Lowercase alphanumeric key, plus dashes and underscores.
     */
    function sanitize_key($key): string
    {
        $key = strtolower((string) $key);

        return (string) preg_replace('/[^a-z0-9_\-]/', '', $key);
    }
}

if (! function_exists('get_post_type')) {
    /**
     * Return the post type registered for a test post ID.
     *
     * @param mixed $post Post ID.
     * @return string|false
     */
    function get_post_type($post = null)
    {
        $id = is_numeric($post) ? (int) $post : 0;
        $map = $GLOBALS['deaktiver_test_post_types'] ?? [];

        return $map[$id] ?? false;
    }
}
