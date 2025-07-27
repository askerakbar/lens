<?php

namespace AskerAkbar\Lens\Service\Listener;

use AskerAkbar\Lens\Service\Profiler\QueryProfiler;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;

class QueryLoggerListener extends AbstractListenerAggregate
{
    protected $profiler;
    
    public function __construct(QueryProfiler $profiler)
    {
        $this->profiler = $profiler;
    }
    
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_FINISH, [$this, 'onFinish'], -1000);
    }
    
    public function onFinish(MvcEvent $event)
    {
        // Save all collected queries at the end of the request
        $this->profiler->saveQueries();
        
    }
}