<?php

namespace AskerAkbar\Lens\Service\Storage;

use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use AskerAkbar\Lens\Service\Utils\QueryUtils;

class QueryStorage implements QueryStorageInterface
{
    protected $dbAdapter;
    protected $maxQueries;
    protected $tableName;
    
    public function __construct(AdapterInterface $dbAdapter, array $config = [])
    {
        $this->dbAdapter = $dbAdapter;
        $this->maxQueries = 200;
        $this->tableName = $config['storage']['table'] ?? 'lens_logs';
    }
    
    /**
     * Store a query with its metadata
     *
     * @param array $queryData
     * @return bool
     */
    public function storeQuery(array $queryData): bool
    {
        try {
            // Format backtrace if present
            if (isset($queryData['backtrace']) && is_array($queryData['backtrace'])) {
                $queryData['backtrace'] = $this->formatBacktrace($queryData['backtrace']);
            }
            
            // Use direct connection for faster inserts
            $tableGateway = new TableGateway($this->tableName, $this->dbAdapter);
            $result = $tableGateway->insert([
                'batch_id' => $queryData['batch_id'],
                'type' => 'query',
                'content' => json_encode($queryData),
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);
            
            // Clean up old queries if we exceed max limit
            $this->cleanupOldQueries();
            
            return $result > 0;
        } catch (\Exception $e) {
            // Log the error but don't crash
            error_log('Error storing query: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get queries with pagination and filtering
     *
     * @param int|null $limit Maximum number of queries to return
     * @param int $offset Number of queries to skip
     * @param array $filters Associative array of filters
     * @return array
     */
    public function getQueries(?int $limit = null, int $offset = 0, array $filters = []): array
    {
        $sql = new Sql($this->dbAdapter);
        $select = new Select($this->tableName);
        $select->order('id DESC');
        
        // Apply filters
        $where = new Where();
        $where->equalTo('type', 'query'); // Only get query type entries
        
        // Apply search filter
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $where->nest()
                ->like('content', $searchTerm)
                ->unnest();
        }
        
        $select->where($where);
        
        if ($limit !== null) {
            $select->limit($limit);
            $select->offset($offset);
        }
        
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        
        $resultSet = new ResultSet();
        $resultSet->initialize($results);
        
        $rows = $resultSet->toArray();
        
        // Process and filter results
        $processedRows = [];
        foreach ($rows as $row) {
            $processedRow = $this->processQueryRow($row);
            
            // Add interpolated query for display if possible
            $content = $processedRow['content'] ?? [];
            if (isset($content['sql']) && isset($content['parameters'])) {
                $processedRow['display_sql'] = QueryUtils::interpolateQueryForDisplay($content['sql'], $content['parameters']);
            } else {
                $processedRow['display_sql'] = null;
            }
            
            // Apply post-processing filters (for complex filters that need decoded content)
            if ($this->matchesFilters($processedRow, $filters)) {
                $processedRows[] = $processedRow;
            }
        }
        
        return $processedRows;
    }
    
    /**
     * Get total count of queries matching filters
     *
     * @param array $filters
     * @return int
     */
    public function getTotalCount(array $filters = []): int
    {
        $sql = new Sql($this->dbAdapter);
        $select = new Select($this->tableName);
        $select->columns(['count' => new \Laminas\Db\Sql\Expression('COUNT(*)')]);
        
        $where = new Where();
        $where->equalTo('type', 'query');
        
        // Apply search filter
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $where->nest()
                ->like('content', $searchTerm)
                ->unnest();
        }
        
        $select->where($where);
        
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        
        $row = $results->current();
        $totalCount = (int) $row['count'];
        
        // For complex filters (slow/failed), we need to apply post-processing
        if (!empty($filters['slow']) || !empty($filters['failed'])) {
            // This is less efficient but necessary for JSON content filtering
            // You might want to consider adding indexed columns for duration and error status
            $allRows = $this->getQueries(null, 0, ['search' => $filters['search'] ?? '']);
            $filteredCount = 0;
            
            foreach ($allRows as $row) {
                if ($this->matchesFilters($row, $filters)) {
                    $filteredCount++;
                }
            }
            
            return $filteredCount;
        }
        
        return $totalCount;
    }
    
    /**
     * Clear all stored queries
     *
     * @return int Number of queries cleared
     */
    public function clearQueries(): int
    {
        $tableGateway = new TableGateway($this->tableName, $this->dbAdapter);
        return $tableGateway->delete(['type' => 'query']);
    }
    
    /**
     * Returns paginated and filtered queries for API response.
     * Handles pagination, offset, and filter criteria.
     *
     * @param int $page
     * @param int $perPage
     * @param string $filter
     * @param string $search
     * @return array
     */
    public function getQueryPage(int $page, int $perPage, string $filter = '', string $search = ''): array
    {
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $filterCriteria = [];
        if (!empty($filter)) {
            switch ($filter) {
                case 'slow':
                    $filterCriteria['slow'] = true;
                    break;
                case 'failed':
                    $filterCriteria['failed'] = true;
                    break;
                // Add more filter types as needed
            }
        }
        if (!empty($search)) {
            $filterCriteria['search'] = trim($search);
        }
        $queries = $this->getQueries($perPage, $offset, $filterCriteria);
        $totalCount = $this->getTotalCount($filterCriteria);
        $totalPages = (int) ceil($totalCount / $perPage);
        return [
            'queries' => $queries,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $totalCount,
            'totalPages' => $totalPages,
            'hasMore' => $page < $totalPages,
            'filter' => $filter,
            'search' => $search,
        ];
    }
    
    /**
     * Process a query row from database
     */
    private function processQueryRow(array $row): array
    {
        if (isset($row['content'])) {
            $content = json_decode($row['content'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Format backtrace for display
                if (isset($content['trace']) && is_array($content['trace'])) {
                    $content['backtrace'] = $content['trace']; // Normalize trace to backtrace
                    $content['backtrace_display'] = $this->formatBacktracesForDisplay($content['trace']);
                }
                
                $row['content'] = $content;
            }
        }
        
        return $row;
    }
    
    /**
     * Check if a processed row matches the given filters
     */
    private function matchesFilters(array $row, array $filters): bool
    {
        $content = $row['content'] ?? [];
        
        // Check slow filter
        if (!empty($filters['slow'])) {
            $duration = $content['duration'] ?? 0;
            if ($duration <= 0.1) { // Not slow (<=100ms)
                return false;
            }
        }
        
        // Check failed filter
        if (!empty($filters['failed'])) {
            if (empty($row['error']) && empty($content['error'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Clean up old queries to maintain max limit
     */
    private function cleanupOldQueries(): void
    {
        if ($this->maxQueries <= 0) {
            return;
        }
        
        try {
            $sql = new Sql($this->dbAdapter);
            
            // Count current queries
            $countSelect = new Select($this->tableName);
            $countSelect->columns(['count' => new \Laminas\Db\Sql\Expression('COUNT(*)')]);
            $countSelect->where(['type' => 'query']);
            
            $statement = $sql->prepareStatementForSqlObject($countSelect);
            $results = $statement->execute();
            $currentCount = (int) $results->current()['count'];
            
            if ($currentCount > $this->maxQueries) {
                $toDelete = $currentCount - $this->maxQueries;
                
                // Delete oldest queries
                $deleteSelect = new Select($this->tableName);
                $deleteSelect->columns(['id']);
                $deleteSelect->where(['type' => 'query']);
                $deleteSelect->order('id ASC');
                $deleteSelect->limit($toDelete);
                
                $statement = $sql->prepareStatementForSqlObject($deleteSelect);
                $results = $statement->execute();
                
                $idsToDelete = [];
                foreach ($results as $row) {
                    $idsToDelete[] = $row['id'];
                }
                
                if (!empty($idsToDelete)) {
                    $tableGateway = new TableGateway($this->tableName, $this->dbAdapter);
                    $tableGateway->delete(['id' => $idsToDelete]);
                }
            }
        } catch (\Exception $e) {
            error_log('Error cleaning up old queries: ' . $e->getMessage());
        }
    }
    
    /**
     * Format backtrace for storage
     */
    private function formatBacktrace(array $backtrace): array
    {
        return array_map(function($trace) {
            return [
                'file' => $trace['file'] ?? '',
                'line' => $trace['line'] ?? 0,
                'function' => $trace['function'] ?? '',
                'class' => $trace['class'] ?? ''
            ];
        }, $backtrace);
    }

    /**
     * Format backtraces for API display
     */
    private function formatBacktracesForDisplay(array $traces): array
    {
        return array_map(function($trace) {
            return ($trace['file'] ?? '') . ':' . ($trace['line'] ?? 0);
        }, $traces);
    }
}