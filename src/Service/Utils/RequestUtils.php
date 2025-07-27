<?php

namespace AskerAkbar\Lens\Service\Utils;

/**
 * Utility class for extracting hostname and request information.
 */
class RequestUtils
{
    /**
     * Extract hostname information.
     *
     * @return string|null
     */
    public static function extractHostname(): ?string
    {
        if (function_exists('gethostname')) {
            return gethostname();
        }
        
        if (php_uname('n')) {
            return php_uname('n');
        }
        
        return null;
    }

    /**
     * Extract request information from $_SERVER.
     *
     * @return string|null
     */
    public static function extractRequestInfo(): ?string
    {
        if (isset($_SERVER['REQUEST_METHOD']) && isset($_SERVER['REQUEST_URI'])) {
            return $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'];
        }
        
        if (isset($_SERVER['REQUEST_URI'])) {
            return $_SERVER['REQUEST_URI'];
        }
        
        return null;
    }
} 