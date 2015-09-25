<?php
namespace Opensymap\Request;

use Opensymap\Lib\Dictionary;

class Request
{
    private $req;
    
    public function __construct(&$get, &$post, &$request, &$cookie, &$files, &$server)
    {
        $this->req = new Dictionary([
            'cookie' => &$cookie,
            'get'    => &$get,
            'input'  => &$request,
            'instance' => null,
            'files'  => &$files,
            'post'   => &$post,
            'raw'    => file_get_contents('php://input'),
            'server' => &$server
        ]);
    }
    
    public function get($key)
    {
        return $this->req->get($key);
    }
    
    public function set($key, $val)
    {
        $this->req->set($key, $val);
    }
}
