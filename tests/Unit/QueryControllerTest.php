<?php

use AskerAkbar\Lens\Controller\QueryController;
use AskerAkbar\Lens\Service\Storage\QueryStorageInterface;
use Tests\TestCase;

class QueryControllerTest extends TestCase
{
    protected QueryStorageInterface $mockStorage;
    protected QueryController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockStorage = $this->createMock(QueryStorageInterface::class);
        $this->controller = new QueryController($this->mockStorage, []);
    }

    public function test_clear_action_returns_success_response()
    {
        $this->mockStorage->method('clearQueries')->willReturn(5);

        $result = $this->controller->clearAction();
        $data = $result->getVariables();
        
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('cleared', $data);
        
        $this->assertTrue($data['success']);
        $this->assertEquals('Queries cleared successfully', $data['message']);
        $this->assertEquals(5, $data['cleared']);
    }

    public function test_clear_action_handles_exception()
    {
        $this->mockStorage->method('clearQueries')->willThrowException(new \Exception('Database error'));

        $result = $this->controller->clearAction();
        $data = $result->getVariables();
        
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('error', $data);
        
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Failed to clear queries', $data['error']);
        $this->assertStringContainsString('Database error', $data['error']);
    }

    public function test_clear_action_sets_500_status_code_on_error()
    {
        $this->mockStorage->method('clearQueries')->willThrowException(new \Exception('Database error'));

        // Mock the response and set it directly on the controller
        $response = $this->createMock(\Laminas\Http\Response::class);
        $response->expects($this->once())->method('setStatusCode')->with(500);
        
        // Use reflection to set the response property
        $reflection = new \ReflectionClass($this->controller);
        $responseProperty = $reflection->getProperty('response');
        $responseProperty->setAccessible(true);
        $responseProperty->setValue($this->controller, $response);
        
        $this->controller->clearAction();
    }
} 