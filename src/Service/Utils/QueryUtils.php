<?php

namespace AskerAkbar\Lens\Service\Utils;

/**
 * Utility class for query formatting and display.
 */
class QueryUtils
{
    /**
     * Interpolates query parameters into a SQL string for display purposes.
     * Handles named and positional parameters, arrays, and common types.
     *
     * @param string $query
     * @param array $params
     * @return string
     */
    public static function interpolateQueryForDisplay($query, $params)
    {
        $indexed = 0;
        foreach ($params as $key => $value) {
            $placeholder = is_string($key) ? ':' . ltrim($key, ':') : '?';

            // If value is array, expand for IN clause
            if (is_array($value)) {
                $displayValue = implode(', ', array_map([self::class, 'formatQueryParamForDisplay'], $value));
                $displayValue = "($displayValue)";
            } else {
                $displayValue = self::formatQueryParamForDisplay($value);
            }

            // Replace all occurrences for named, first for positional
            if ($placeholder === '?') {
                $query = preg_replace('/\?/', $displayValue, $query, 1);
            } else {
                // Use word boundary to avoid partial replacements
                $query = preg_replace('/' . preg_quote($placeholder, '/') . '\b/', $displayValue, $query);
            }
        }
        return $query;
    }

    /**
     * Formats a single query parameter for display.
     *
     * @param mixed $value
     * @return string
     */
    public static function formatQueryParamForDisplay($value)
    {
        if (is_null($value)) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        if ($value instanceof \DateTimeInterface) {
            return "'" . $value->format('Y-m-d H:i:s') . "'";
        }
        if (is_string($value)) {
            return "'" . str_replace("'", "''", $value) . "'";
        }
        if (is_numeric($value)) {
            return $value;
        }
        if (is_array($value)) {
            // Handled in interpolateQueryForDisplay
            return '';
        }
        // Fallback for objects/resources
        return "'[UNSUPPORTED TYPE]'";
    }
} 