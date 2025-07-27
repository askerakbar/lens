<?php

namespace AskerAkbar\Lens\Service\Storage;

class FileStorage implements QueryStorageInterface
{
    protected string $logFile;
    protected int $maxQueries;

    public function __construct(array $config = [])
    {
        $this->logFile = $config['storage']['file'] ?? __DIR__ . '/../../../data/query_logs.log';
        $this->maxQueries = $config['storage']['max_queries'] ?? 200;
        $this->ensureLogFile();
    }

    private function ensureLogFile(): void
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (!file_exists($this->logFile)) {
            touch($this->logFile);
        }
    }

    public function storeQuery(array $queryData): bool
    {
        $this->ensureLogFile();
        $queries = $this->readAllQueries();
        $lastId = 0;
        if (!empty($queries)) {
            $last = end($queries);
            $lastId = isset($last['id']) ? (int)$last['id'] : 0;
        }
        $queryData['id'] = $lastId + 1;
        $line = json_encode($queryData) . "\n";
        $fp = fopen($this->logFile, 'a');
        if (!$fp) return false;
        flock($fp, LOCK_EX);
        fwrite($fp, $line);
        flock($fp, LOCK_UN);
        fclose($fp);
        $this->cleanupOldQueries();
        return true;
    }

    public function getQueries(?int $limit = null, int $offset = 0, array $filters = []): array
    {
        $queries = $this->readAllQueries();
        $queries = array_reverse($queries); // newest first
        $filtered = array_filter($queries, function($q) use ($filters) {
            return $this->matchesFilters($q, $filters);
        });
        $result = array_slice(array_values($filtered), $offset, $limit ?? null);
        
        // Format results to match database storage format
        return array_map(function($query) {
            // Add interpolated query for display if possible
            $display_sql = null;
            if (isset($query['sql']) && isset($query['parameters'])) {
                $display_sql = \AskerAkbar\Lens\Service\Utils\QueryUtils::interpolateQueryForDisplay($query['sql'], $query['parameters']);
            }
            
            return [
                'id' => $query['id'] ?? null,
                'content' => $query,
                'created_at' => $query['timestamp'] ?? date('Y-m-d H:i:s'),
                'error' => !empty($query['error']),
                'display_sql' => $display_sql,
            ];
        }, $result);
    }

    public function getTotalCount(array $filters = []): int
    {
        $queries = $this->readAllQueries();
        $filtered = array_filter($queries, function($q) use ($filters) {
            return $this->matchesFilters($q, $filters);
        });
        return count($filtered);
    }

    public function clearQueries(): int
    {
        $count = count($this->readAllQueries());
        file_put_contents($this->logFile, '');
        return $count;
    }

    public function getQueryPage(int $page, int $perPage, string $filter = '', string $search = ''): array
    {
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $filters = [];
        if (!empty($filter)) {
            switch ($filter) {
                case 'slow':
                    $filters['slow'] = true;
                    break;
                case 'failed':
                    $filters['failed'] = true;
                    break;
            }
        }
        if (!empty($search)) {
            $filters['search'] = trim($search);
        }
        $queries = $this->getQueries($perPage, $offset, $filters);
        $totalCount = $this->getTotalCount($filters);
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

    private function readAllQueries(): array
    {
        $this->ensureLogFile();
        $lines = @file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) return [];
        $queries = [];
        foreach ($lines as $line) {
            $q = json_decode($line, true);
            if (is_array($q)) {
                $queries[] = $q;
            }
        }
        return $queries;
    }

    private function matchesFilters(array $query, array $filters): bool
    {
        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $haystack = json_encode($query);
            if (stripos($haystack, $search) === false) {
                return false;
            }
        }
        // Slow filter
        if (!empty($filters['slow'])) {
            $duration = $query['duration'] ?? 0;
            if ($duration <= 0.1) {
                return false;
            }
        }
        // Failed filter
        if (!empty($filters['failed'])) {
            if (empty($query['error'])) {
                return false;
            }
        }
        return true;
    }

    private function cleanupOldQueries(): void
    {
        if ($this->maxQueries <= 0) return;
        $queries = $this->readAllQueries();
        $count = count($queries);
        if ($count > $this->maxQueries) {
            $toKeep = array_slice($queries, -$this->maxQueries);
            $lines = array_map(fn($q) => json_encode($q), $toKeep);
            file_put_contents($this->logFile, implode("\n", $lines) . "\n");
        }
    }
} 