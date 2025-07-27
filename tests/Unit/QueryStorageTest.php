<?php

use AskerAkbar\Lens\Service\Storage\QueryStorage;
use AskerAkbar\Lens\Service\Utils\QueryUtils;
use Tests\TestCase;

class QueryStorageTest extends TestCase
{
    public function test_interpolate_query_for_display_with_named_parameters()
    {
        $sql = "SELECT * FROM users WHERE id = :id AND name = :name";
        $params = [
            'id' => 123,
            'name' => 'John Doe'
        ];
        
        $result = QueryUtils::interpolateQueryForDisplay($sql, $params);
        
        $this->assertEquals("SELECT * FROM users WHERE id = 123 AND name = 'John Doe'", $result);
    }

    public function test_interpolate_query_for_display_with_positional_parameters()
    {
        $sql = "SELECT * FROM users WHERE id = ? AND name = ?";
        $params = [123, 'John Doe'];
        
        $result = QueryUtils::interpolateQueryForDisplay($sql, $params);
        
        $this->assertEquals("SELECT * FROM users WHERE id = 123 AND name = 'John Doe'", $result);
    }

    public function test_interpolate_query_for_display_with_null_parameters()
    {
        $sql = "SELECT * FROM users WHERE deleted_at = :deleted_at";
        $params = ['deleted_at' => null];
        
        $result = QueryUtils::interpolateQueryForDisplay($sql, $params);
        
        $this->assertEquals("SELECT * FROM users WHERE deleted_at = NULL", $result);
    }

    public function test_interpolate_query_for_display_with_array_parameters()
    {
        $sql = "SELECT * FROM users WHERE id IN (:ids)";
        $params = ['ids' => [1, 2, 3]];
        
        $result = QueryUtils::interpolateQueryForDisplay($sql, $params);
        
        $this->assertEquals("SELECT * FROM users WHERE id IN ((1, 2, 3))", $result);
    }

    public function test_format_query_param_for_display_with_string()
    {
        $result = QueryUtils::formatQueryParamForDisplay('test string');
        $this->assertEquals("'test string'", $result);
    }

    public function test_format_query_param_for_display_with_integer()
    {
        $result = QueryUtils::formatQueryParamForDisplay(123);
        $this->assertEquals("123", $result);
    }

    public function test_format_query_param_for_display_with_null()
    {
        $result = QueryUtils::formatQueryParamForDisplay(null);
        $this->assertEquals("NULL", $result);
    }

    public function test_format_query_param_for_display_with_boolean()
    {
        $result = QueryUtils::formatQueryParamForDisplay(true);
        $this->assertEquals("TRUE", $result);
        
        $result = QueryUtils::formatQueryParamForDisplay(false);
        $this->assertEquals("FALSE", $result);
    }
}

// File storage tests temporarily disabled
/*
class FileStorageTest extends TestCase
{
    private $logFile;
    private $storage;

    protected function setUp(): void
    {
        $this->logFile = sys_get_temp_dir() . '/test_query_logs_' . uniqid() . '.log';
        $config = [
            'storage' => [
                'type' => 'file',
                'file' => $this->logFile,
                'max_queries' => 10,
            ]
        ];
        $this->storage = new \AskerAkbar\Lens\Service\Storage\FileStorage($config);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testStoreAndRetrieveQuery()
    {
        $query = [
            'sql' => 'SELECT 1',
            'duration' => 0.2,
            'error' => null,
        ];
        $this->assertTrue($this->storage->storeQuery($query));
        $results = $this->storage->getQueries();
        $this->assertCount(1, $results);
        $this->assertEquals('SELECT 1', $results[0]['content']['sql']);
    }

    public function testPagination()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->storage->storeQuery(['sql' => 'Q' . $i, 'duration' => 0.2]);
        }
        $results = $this->storage->getQueries(2, 1); // limit 2, offset 1
        $this->assertCount(2, $results);
        $this->assertEquals('Q3', $results[0]['content']['sql']);
        $this->assertEquals('Q2', $results[1]['content']['sql']);
    }

    public function testFiltering()
    {
        $this->storage->storeQuery(['sql' => 'fast', 'duration' => 0.05]);
        $this->storage->storeQuery(['sql' => 'slow', 'duration' => 0.5]);
        $this->storage->storeQuery(['sql' => 'fail', 'duration' => 0.2, 'error' => 'oops']);
        $slow = $this->storage->getQueries(null, 0, ['slow' => true]);
        $this->assertCount(2, $slow); // 'slow' and 'fail' (duration > 0.1)
        $failed = $this->storage->getQueries(null, 0, ['failed' => true]);
        $this->assertCount(1, $failed);
        $this->assertEquals('fail', $failed[0]['content']['sql']);
        $search = $this->storage->getQueries(null, 0, ['search' => 'slow']);
        $this->assertCount(1, $search);
        $this->assertEquals('slow', $search[0]['content']['sql']);
    }

    public function testClearQueries()
    {
        $this->storage->storeQuery(['sql' => 'A']);
        $this->storage->storeQuery(['sql' => 'B']);
        $count = $this->storage->clearQueries();
        $this->assertEquals(2, $count);
        $this->assertCount(0, $this->storage->getQueries());
    }
}
*/ 