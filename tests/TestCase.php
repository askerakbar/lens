<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;

abstract class TestCase extends BaseTestCase
{
    protected function createMockParamsPlugin(array $queryParams = []): Params
    {
        $params = $this->createMock(Params::class);
        $params->method('fromQuery')->willReturnCallback(function ($key, $default = null) use ($queryParams) {
            return $queryParams[$key] ?? $default;
        });
        return $params;
    }

    protected function createMockPluginManager(Params $params): PluginManager
    {
        $pluginManager = $this->createMock(PluginManager::class);
        $pluginManager->method('get')->willReturn($params);
        return $pluginManager;
    }

    protected function createMockRequest(array $queryParams = []): Request
    {
        $request = $this->createMock(Request::class);
        $request->method('getQuery')->willReturn((object) $queryParams);
        return $request;
    }

    protected function createMockResponse(): Response
    {
        return $this->createMock(Response::class);
    }

    protected function createMockMvcEvent(Request $request = null, Response $response = null): MvcEvent
    {
        $event = $this->createMock(MvcEvent::class);
        $event->method('getRequest')->willReturn($request ?? $this->createMockRequest());
        $event->method('getResponse')->willReturn($response ?? $this->createMockResponse());
        return $event;
    }

    protected function createMockRouteMatch(array $params = []): RouteMatch
    {
        return $this->createMock(RouteMatch::class);
    }
}
