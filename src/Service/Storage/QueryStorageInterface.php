<?php

namespace AskerAkbar\Lens\Service\Storage;

interface QueryStorageInterface
{
    /**
     * Store a query with its metadata
     */
    public function storeQuery(array $queryData): bool;
    
    /**
     * Get queries with pagination and filtering
     *
     * @param int|null $limit Maximum number of queries to return
     * @param int $offset Number of queries to skip
     * @param array $filters Associative array of filters:
     *   - 'search' => string: Search term for SQL content
     *   - 'slow' => bool: Filter for slow queries
     *   - 'failed' => bool: Filter for failed queries
     * @return array
     */
    public function getQueries(?int $limit = null, int $offset = 0, array $filters = []): array;
    
    /**
     * Get total count of queries matching filters
     */
    public function getTotalCount(array $filters = []): int;
    
    /**
     * Clear all stored queries
     *
     * @return int Number of queries cleared
     */
    public function clearQueries(): int;
    
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
    public function getQueryPage(int $page, int $perPage, string $filter = '', string $search = ''): array;
}