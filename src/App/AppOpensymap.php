<?php
namespace Opensymap\App;

use Opensymap\Lib\Dictionary;
use Opensymap\Driver\DboFactory;

class AppOpensymap extends AppPrototype
{
    private $dbo; //Connessione al db osy;
    
    public function __construct($dbo, $request)
    {
        $this->dbo = DboFactory::init();
        $this->request = $request;
    }
    
    public function response()
    {       
        $this->controller = new Controller($this->dbo, $this->request);
        return $this->controller->exec();
    }
}

