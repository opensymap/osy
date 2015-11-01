<?php
namespace Opensymap\Event;

class Listener
{
    private $repo;
    
    public function __construct($id, $event, $owner=null)
    {
        $this->repo['id'] = $id;
        $this->repo['event'] = $event;
        $this->repo['owner'] = $owner;
    }
    
    public function setClosure($function)
    {
        if (empty($function)) {
            return;
        }
        if (!is_object($function) && !($function instanceof Closure)) {
            if (!($function = @create_function('$Db, $response, $context', $function.PHP_EOL))) {
                $e = error_get_last();
                $error  = 'TRIGGER : '.$this->getId().PHP_EOL;
                $error .= 'EVENT   : '.$this->getEvent().PHP_EOL;
                $error .= 'LINE    : '.$e['line'].PHP_EOL;
                $error .= 'MESSAGE : '.$e['message'].PHP_EOL;
                $error .= 'FUNCTION :'.print_r($function,true).PHP_EOL;
                return nl2br($error);
            }
        }
        $this->repo['function'] = $function;
    }
    
    public function execute($db, $response, $context)
    {
        if (empty($this->repo['function'])) {
            return;
        }
        try {
            return $this->repo['function']($db, $response, $context);
        } catch (Error $e) {
            return $e->getMessage();
        }
    }
    
    public function getId()
    {
        return $this->repo['id'];
    }
    
    public function getSha1Id()
    {
        return sha1($this->repo['id']);
    }
    
    public function getEvent()
    {
        return $this->repo['event'];
    }
    
    public function getOwner()
    {
        return $this->repo['owner'];
    }
}
