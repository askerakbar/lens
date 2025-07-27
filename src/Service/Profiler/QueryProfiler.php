<?php

namespace AskerAkbar\Lens\Service\Profiler;

use AskerAkbar\Lens\Service\Storage\QueryStorageInterface;
use Laminas\Db\Adapter\Profiler\ProfilerInterface;
use Laminas\Db\Adapter\StatementContainerInterface;
use Ramsey\Uuid\Uuid;
use Laminas\Db\Adapter\AdapterInterface;
use AskerAkbar\Lens\Service\Utils\BacktraceUtils;
use AskerAkbar\Lens\Service\Utils\ConnectionUtils;
use AskerAkbar\Lens\Service\Utils\RequestUtils;

/**
 * Query profiler that implements Laminas ProfilerInterface.
 * Responsible for profiling database queries and storing them.
 */
class QueryProfiler implements ProfilerInterface
{
    protected array $queries = [];
    protected ?string $currentQueryId = null;
    protected QueryStorageInterface $storage;
    protected array $config;
    protected string $batchId;
    protected ?AdapterInterface $adapter;
    protected bool $isRecording = false;

    public function __construct(QueryStorageInterface $storage, array $config = [], ?AdapterInterface $adapter = null)
    {
        $this->storage = $storage;
        $this->config = $config;
        $this->batchId = Uuid::uuid4()->toString();
        $this->adapter = $adapter;
    }

    /**
     * Start profiling a query.
     *
     * @param mixed $target
     * @return $this
     */
    public function profilerStart($target)
    {
        if (!($this->config['enabled'] ?? true)) {
            return $this;
        }

        $sql = '';
        $parameters = [];
        
        if ($target instanceof StatementContainerInterface) {
            $sql = $target->getSql();
            $parameters = $target->getParameterContainer() ? $target->getParameterContainer()->getNamedArray() : [];
        } else {
            $sql = $target;
        }
        
        // Skip logger-related queries
        if (is_string($sql) && stripos($sql, 'lens_logs') !== false) {
            return $this;
        }

        $queryId = Uuid::uuid4()->toString();
        $this->queries[$queryId] = [
            'batch_id' => $this->batchId,
            'sql' => $sql,
            'parameters' => $parameters,
            'start_time' => microtime(true),
            'end_time' => null,
            'duration' => null,
            'trace' => BacktraceUtils::buildFilteredBacktrace(__CLASS__, ['profilerStart']),
            'timestamp' => date('Y-m-d H:i:s'),
            'connection' => ConnectionUtils::extractConnectionInfo($this->adapter),
            'hostname' => RequestUtils::extractHostname(),
            'request' => RequestUtils::extractRequestInfo(),
        ];
        
        $this->currentQueryId = $queryId;
        return $this;
    }

    /**
     * Finish profiling the current query.
     *
     * @return $this
     */
    public function profilerFinish()
    {
        if ($this->currentQueryId && isset($this->queries[$this->currentQueryId])) {
            $this->queries[$this->currentQueryId]['end_time'] = microtime(true);
            $this->queries[$this->currentQueryId]['duration'] = 
                $this->queries[$this->currentQueryId]['end_time'] - 
                $this->queries[$this->currentQueryId]['start_time'];
            $this->currentQueryId = null;
        }
        
        return $this;
    }
    
    /**
     * Store all profiled queries in the storage.
     */
    public function saveQueries(): void
    {
        if ($this->isRecording || empty($this->queries)) {
            return;
        }
        
        $this->isRecording = true;
        
        try {
            foreach ($this->queries as $query) {
                $this->storage->storeQuery($query);
            }
            $this->queries = [];
        } catch(\Exception $e) {
            error_log('QueryProfiler save error: ' . $e->getMessage());
        } finally {
            $this->isRecording = false;
        }
    }
}