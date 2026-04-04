<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Escape special characters in user input for SQL LIKE queries.
 *
 * This prevents users from injecting wildcard characters (%, _)
 * into search queries, which could cause unexpected matching
 * behavior or performance degradation.
 */
final class SearchEscape
{
    /**
     * Wrap a search term for use in LIKE queries with proper escaping.
     *
     * Returns a string like "%escaped_term%" ready for use in
     * ->where('column', 'like', $result).
     */
    public static function like(string $term): string
    {
        $escaped = addcslashes(trim($term), '%_\\');

        return '%' . $escaped . '%';
    }
}
