<?php
namespace Opensymap\Ocl\View;

use Opensymap\Response\PageHtmlResponse;

abstract class AbstractView
{
    public $model;
    public $request;
    public $response;
    public $dispatcher;
    
    public function __construct($dispatcher, $model)
    {
        $this->dispatcher = $dispatcher;
        $this->model = $model;
        $this->request = $this->model->request;
        $this->response = new PageHtmlResponse();
    }
    
    public function __toString()
    {
        return $this->get();
    }
    
    public function get()
    {
        $this->build();
        return $this->response;
    }
    
    abstract protected function build();
}
