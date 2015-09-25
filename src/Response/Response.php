<?php
namespace Opensymap\Response;

use Opensymap\Lib\Dictionary;

abstract class Response 
{
    protected $repo;
    
    public function __construct()
    {
        $this->repo = new Dictionary(array(
            'content'=>array(),
            'header'=>array()
        ));
    }
    
    public function addBufferToContent($path = null, $part = 'main')
    {
        $this->addContent($this->getBuffer($path) , $part);
    }
    
    public function addContent($content, $part = 'main')
    {
        $this->repo->set('content.'.$part, $content);
    }
    
    public function exec()
    {
        $this->sendHeader();
        echo implode('', $this->repo->get('content'));
    }
    
    public static function getBuffer($path = null)
    {
        $buffer = 1;
        if (!empty($path)) {
            if (is_file($path)) {
                $buffer = include($path);
            } else {
                throw new \Exception('File '.$path.' not exists');
            }
        }
        if ($buffer === 1) {
            $buffer = ob_get_contents();
            ob_clean();
        }
        return $buffer;
    }
    
    public function setContentType($type)
    {
        $this->repo->set('header.Content-Type', $type);
    }
    
    public function setHeader($key, $value)
    {
        $this->repo->set('header.'.$key, $value);
    }
    
    public static function cookie($vid, $vval, $sca = null)
    {        
        $dom = $_SERVER['HTTP_HOST'];
        $app = explode('.',$dom);
        if (count($app) == 3){ $dom = ".".$app[1].".".$app[2]; }
        if (empty($sca)) $sca = mktime(0,0,0,date('m'),date('d'),date('Y')+1);
        setcookie($vid, $vval, $sca, "/", $dom);
    }
    
    protected function sendHeader()
    {
        $header = $this->repo->get('header');
        foreach ($header as $key => $value) {
            header($key.': '.$value);
        }
    }
    
    public function get($key)
    {
        return $this->repo->get($key);
    }
    
    
    public function set($key, $val)
    {
        $this->repo->set($key, $val);
        return $this;
    }
    
    public function message($message)
    {
        $this->repo->set('message', $message, true);
    }
    
    abstract public function __toString();
}