<?php
namespace Opensymap\Response;

class PageHtmlResponse extends Response
{
    private $template = null;
    
    public function __construct($templateId)
    {
        $this->setContentType('text/html');
        $this->repo['content'] = array('main' => '');
        if (!empty($templateId)) {
            $this->template = $this->loadTemplate($templateId);
        }
    }
    
    public function addBufferToContent($path = null, $part = 'main')
    {
        $buffer = self::getBuffer($path);
        $buffer = $this->replaceContent($buffer);
        $this->addContent($buffer , $part);
    }
    
    /*
    public static function loadTemplate($templateId)
    {
        $template = '';
        if ($path = Kernel::get('layouts.'.$templateId)) {
            $template = self::getBuffer($path);
        }
        return $template;
    }
    */
    
    private function replaceContent($buffer)
    {
        $dummy = array_map(function($v){
                     return '<!--'.$v.'-->';
                 },array_keys($this->repo['content']));
        $parts = array_map(function($p){
                    return is_array($p) ? implode("\n",$p) : $p;
                },array_values($this->repo['content']));
        return str_replace($dummy, $parts, $buffer);
    }
    
    public function __toString()
    {
        $this->sendHeader();
        if (empty($this->template)) {
            return implode('', $this->repo['content']);
        } else {
            return $this->replaceContent($this->template);
        }
    }
}