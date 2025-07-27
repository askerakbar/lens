<?php

declare(strict_types=1);

namespace AskerAkbar\Lens;

use AskerAkbar\Lens\Service\Profiler\QueryProfiler;
use Laminas\EventManager\EventInterface;
use AskerAkbar\Lens\Service\Listener\QueryLoggerListener;
use Laminas\ModuleManager\Feature\ConfigProviderInterface;
use Laminas\ModuleManager\Feature\BootstrapListenerInterface;

class Module  implements ConfigProviderInterface, BootstrapListenerInterface
{
    public function getConfig(): array
    {
        $config = include __DIR__ . '/../config/module.config.php';
        return $config;
    }

    public function onBootstrap(EventInterface $e)
    {
        $application = $e->getApplication();
        $serviceManager = $application->getServiceManager();
        $eventManager = $application->getEventManager();
        // Create and attach the profiler
        $profiler = $serviceManager->get(QueryProfiler::class);
        // Attach to default adapter if available
        if ($serviceManager->has('Laminas\Db\Adapter\Adapter')) {
            $adapter = $serviceManager->get('Laminas\Db\Adapter\Adapter');
            $adapter->setProfiler($profiler);
        }
        // Attach listener for end-of-request processing
        $listener = new QueryLoggerListener($profiler);
        $listener->attach($eventManager);
    }
}
