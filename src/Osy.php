<?php
namespace Opensymap;

use Opensymap\App\Controller;
use Opensymap\Driver\DboFactory;
use Opensymap\Router\Router;
use Opensymap\Request\Request;
use Opensymap\Session\Session;

class Osy
{
    public $dbo;
    public $request;
    public $session;
    
    public function __construct()
    {
        //Get handler 
        $this->dbo = DboFactory::init();
        
        //Get new request object;
        $this->request = new Request(
            $_GET, 
            $_POST, 
            $_REQUEST, 
            $_COOKIE, 
            $_FILES, 
            $_SERVER
        );
		
        //Load session;
        $this->session = new Session(
            $this->request->get('input.osy.sid'), 
            $this->dbo
        );
        //Set session handler
        session_set_save_handler($this->session, true);

        //Get new router instance;
        $router = new Router(
            $this->dbo, 
            $this->request
        );

        //Set instance data into request;
        $this->request->set(
            'instance', 
            $router->getInstance()
        );

        //Start controller;
        $this->controller = new Controller(
            $this->dbo, 
            $this->request, 
            $this->session
        );
    }
    
    public function run()
    {
        //Flush response
        return $this->controller->getResponse();
    }
}
