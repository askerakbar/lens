<?php

declare(strict_types=1);

namespace AskerAkbar\Lens;

use Laminas\Router\Http\Literal;
use AskerAkbar\Lens\Service\Storage\QueryStorage;
use AskerAkbar\Lens\Service\Profiler\QueryProfiler;
use AskerAkbar\Lens\Service\Listener\QueryLoggerListener;
use AskerAkbar\Lens\Service\Profiler\QueryProfilerFactory;
use AskerAkbar\Lens\Service\Storage\QueryStorageInterface;
use AskerAkbar\Lens\Service\Command\PublishMigrationCommand;

return [
    'router' => [
        'routes' => [
            'lens-api' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/lens/api/queries',
                    'defaults' => [
                        'controller' => Controller\QueryController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'lens-api-clear' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/lens/api/queries/clear',
                    'defaults' => [
                        'controller' => Controller\QueryController::class,
                        'action'     => 'clear',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [],
            ],
            'lens_home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/lens',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => function($container) {
                $config = $container->get('config')['lens'] ?? [];
                return new Controller\IndexController($config);
            },
            Controller\QueryController::class => function($container) {
                $queryStorage = $container->get(\AskerAkbar\Lens\Service\Storage\QueryStorageInterface::class);
                $config = $container->get('config')['lens'] ?? [];
                return new Controller\QueryController($queryStorage, $config);
            },
        ],
    ],
    'asset_manager' => [
        'resolver_configs' => [
            'paths' => [
                __DIR__ . '/../public/'
            ],
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'asker-akbar/lens/index/index' => __DIR__ . '/../view/index/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'strategies' => [
            'ViewJsonStrategy', // to enable JSON responses
        ],
    ],
    'service_manager' => [
        'factories' => [
            QueryProfiler::class => QueryProfilerFactory::class,
            QueryStorageInterface::class => function ($container) {
                $config = $container->get('config')['lens'] ?? [];
                $storageType = $config['storage']['type'] ?? 'database';
                if ($storageType === 'database') {
                    $adapter = $container->get("Laminas\Db\Adapter\Adapter");
                    return new QueryStorage($adapter, $config);
                } else {
                    // File storage is temporarily disabled
                    throw new \RuntimeException('File storage is temporarily disabled. Please use database storage.');
                }
            },
            QueryLoggerListener::class => function ($container) {
                return new QueryLoggerListener(
                    $container->get(QueryProfiler::class)
                );
            },
            PublishMigrationCommand::class => function($container) {
                return new PublishMigrationCommand();
            },
        ],
    ],
    'laminas-cli' => [
        'commands' => [
            'lens:publish-migration' => PublishMigrationCommand::class,
        ],
    ],
];
