<?php
namespace Opensymap\App;

abstract class AppPrototype
{
    protected $controller;
    protected $dbApp;    
    protected $model;
    protected $request;
    protected $response;
    protected $session;

    public function getController()
    {
        return $this->controller;
    }

    public function getDb()
    {
        return $this->dbApp;
    }
    
    public function getModel()
    {
        return $this->model;
    }
    
    public function getResponse()
    {
        return $this->response;
    }
    
    public function getSession()
    {
        return $this->session;
    }
}
