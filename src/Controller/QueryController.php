<?php

declare(strict_types=1);

namespace AskerAkbar\Lens\Controller;

use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use AskerAkbar\Lens\Service\Storage\QueryStorageInterface;

class QueryController extends AbstractActionController
{
    private QueryStorageInterface $queryStorage;
    private array $config;
    
    public function __construct(QueryStorageInterface $queryStorage, array $config)
    {
        $this->queryStorage = $queryStorage;
        $this->config = $config;
    }

    /**
     * Returns a paginated and filtered list of queries as JSON.
     */
    public function indexAction(): JsonModel
    {
        $page = (int) $this->params()->fromQuery('page', 1);
        $perPage = (int) $this->params()->fromQuery('perPage', 20);
        $filter = $this->params()->fromQuery('filter', '');
        $search = $this->params()->fromQuery('search', '');
        try {
            $result = $this->queryStorage->getQueryPage($page, $perPage, $filter, $search);
            return new JsonModel($result);
        } catch (\Throwable $e) {
            $this->getResponse()->setStatusCode(500);
            return new JsonModel([
                'success' => false,
                'error' => 'Database unavailable or logs table missing.'
            ]);
        }
    }
    
    
    public function clearAction(): JsonModel
    {
        try {
            $cleared = $this->queryStorage->clearQueries();
            return new JsonModel([
                'success' => true,
                'message' => 'Queries cleared successfully',
                'cleared' => $cleared
            ]);
        } catch (\Exception $e) {
            $this->getResponse()->setStatusCode(500);
            return new JsonModel([
                'success' => false,
                'error' => 'Failed to clear queries: ' . $e->getMessage()
            ]);
        }
    }

}