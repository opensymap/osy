<?php
namespace Opensymap\Response;

class JsonResponse extends Response implements ResponseInterface 
{
    public function __construct()
    {
        parent::__construct();
        $this->setContentType('application/json; charset=utf-8');
    }
    
    public function __toString()
    {
        $this->sendHeader();
        $resp = json_encode($this->repo->get('content'));
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return $resp;
                //no break;
            case JSON_ERROR_DEPTH:
                $resp = ' - Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $resp = ' - Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $resp = ' - Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $resp = ' - Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $resp = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $resp = ' - Unknown error';
                break;
        }
        return $resp;
    }
    
    public function appendMessage($message)
    {
        if (!$this->repo->keyExists('content.message')) {
            $this->repo->set('content.message', array());
        }
        $this->set('content.message.'.$message->id, $message->get(), true);
        return $message;
    }
    
    public function command($command, $parameters = null)
    {
        //Format of command is : [command, parameter1, parameter2 ....]
        if (is_array($parameters)){
            array_unshift($parameters,$command);
        } else {
            $parameters = [$command, $parameters];
        }
        $this->message('command', $parameters);
    }
    
    public function debug($msg)
    {
        $this->message('errors', array('alert',$msg));
        $this->dispatch();
    }
    
    public function dispatch()
    {
        $this->sendHeader();
        die($this->__toString());
    }
    
    public function error($oid=null, $err=null)
    {
        if (is_null($oid) || is_null($err)) {
            return $this->repo->keyIsEmpty('content.errors');
        }
        $this->message('errors', array($oid, $err));
    }
    
    public function go($url)
    {
        $this->message('command', array('goto', $url));
    }

    public function message($typ, $val)
    {
        if (!$this->repo->keyExists('content.'.$typ)){
            $this->repo->set('content.'.$typ, array());
        }
        $this->repo->set('content.'.$typ, $val, true);
    }
}
