<?php

namespace AskerAkbar\Lens\Service\Utils;

use Laminas\Db\Adapter\AdapterInterface;

/**
 * Utility class for extracting connection information from database adapters.
 */
class ConnectionUtils
{
    /**
     * Extract connection information from a database adapter.
     *
     * @param AdapterInterface|null $adapter
     * @return array
     */
    public static function extractConnectionInfo(?AdapterInterface $adapter): array
    {
        $connection = [
            'driver' => null,
            'database' => null,
        ];

        if (!$adapter) {
            return $connection;
        }

        try {
            $driver = $adapter->getDriver();
            $connectionParams = method_exists($driver, 'getConnection') 
                ? $driver->getConnection()->getConnectionParameters() 
                : [];
            
            $connection['driver'] = method_exists($driver, 'getName') 
                ? $driver->getName() 
                : get_class($driver);
            
            // Try common keys for database name
            if (isset($connectionParams['database'])) {
                $connection['database'] = $connectionParams['database'];
            } elseif (isset($connectionParams['dbname'])) {
                $connection['database'] = $connectionParams['dbname'];
            }
        } catch (\Throwable $e) {
            // Fallback to nulls if any error
        }

        return $connection;
    }
} 