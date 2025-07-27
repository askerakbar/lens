<?php

namespace AskerAkbar\Lens\Service\Utils;

/**
 * Utility class for building and filtering backtraces.
 */
class BacktraceUtils
{
    /**
     * Build a filtered backtrace for query profiling.
     *
     * @param string $currentClass The current class to skip
     * @param array $skipMethods Methods to skip from the current class
     * @return array
     */
    public static function buildFilteredBacktrace(string $currentClass, array $skipMethods = []): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $relevantTraces = [];

        foreach ($trace as $item) {
            $class = $item['class'] ?? null;
            $file = $item['file'] ?? '';
            $function = $item['function'] ?? '';
            $line = $item['line'] ?? 0;

            // Skip ignored classes
            if (self::shouldIgnoreClass($class)) {
                continue;
            }

            // Skip specific methods from current class
            if ($class === $currentClass && in_array($function, $skipMethods)) {
                continue;
            }

            if ($class && method_exists($class, $function)) {
                try {
                    $reflector = new \ReflectionMethod($class, $function);
                    $file = $reflector->getFileName();
                    $line = $reflector->getStartLine();
                } catch (\Exception $e) {
                    // Keep original file/line if reflection fails
                }
            }

            $relevantTraces[] = [
                'file' => $file,
                'line' => $line,
                'function' => $function,
                'class' => $class,
            ];
        }

        return $relevantTraces;
    }

    /**
     * Check if a class should be ignored in backtraces.
     *
     * @param string|null $class
     * @return bool
     */
    private static function shouldIgnoreClass(?string $class): bool
    {
        if (!$class) {
            return false;
        }

        $ignoredPrefixes = [
            'Psr\\',
            'Composer\\',
            'Lens\\Service\\',
            'Laminas\\',
            'AskerAkbar\\Lens\\Service\\Utils\\',
        ];

        foreach ($ignoredPrefixes as $prefix) {
            if (strpos($class, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }
} 