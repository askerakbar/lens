<?php

declare(strict_types=1);

namespace AskerAkbar\Lens\Controller;

use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    private array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function indexAction(): ViewModel
    {
        $viewModel = new ViewModel();
        $viewModel->setTerminal(true); // This disables the layout
        return $viewModel;
    }
}
