<?php

namespace AskerAkbar\Lens\Service\Profiler;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use AskerAkbar\Lens\Service\Profiler\QueryProfiler;
use AskerAkbar\Lens\Service\Storage\QueryStorageInterface;

/**
 * Factory for creating QueryProfiler instances.
 */
class QueryProfilerFactory implements FactoryInterface
{
    /**
     * Create a QueryProfiler instance.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return QueryProfiler
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): QueryProfiler
    {
        $queryStorage = $container->get(QueryStorageInterface::class);
        $config = $container->get('config')['lens'] ?? [];
        
        // Try to get the database adapter, but don't fail if it's not available
        $adapter = null;
        try {
            $adapter = $container->get('Laminas\\Db\\Adapter\\Adapter');
        } catch (\Exception $e) {
            // Adapter not available, continue without it
        }
        
        return new QueryProfiler($queryStorage, $config, $adapter);
    }
}