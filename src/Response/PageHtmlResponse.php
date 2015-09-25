<?php
namespace Opensymap\Response;

use Opensymap\Lib\Tag;

class PageHtmlResponse extends Response implements ResponseInterface
{   
    public $printMicrotime = false;
    
    public function __construct()
    {
        parent::__construct();
        $this->setHeader('Pragma', ' public');
        $this->setHeader('Cache-Control', 'max-age=86400');
        $this->setHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
        $this->setContentType('text/html; charset=utf-8');
        $this->repo->set('doctype', 'html');
        $this->repo->set('section.head',new Tag('head'));
        $this->repo->set('section.body', new Tag('body'));
        $this->repo->set('head.css', array());
        $this->repo->set('head.jsfile', array());
        $this->repo->set('body.jscode', array());
        $this->repo->set('body.error',array());
    }

    public function addBody($o)
    {
        return $this->get('section.body')->add($o);
    }

    public function addCss($href)
    {
        $fileId = 'head.css.'.sha1($href);
        if (!empty($href) && !$this->repo->keyExists($fileId)) {
            if ($href[0] != '/') {
                $href = OSY_WEB_ROOT.'/'.$href;
            }
            $this->set($fileId, $href);
        }
        return $this;
    }

    public function addMeta()
    {
        return $this->get('head')->add(tag::create('meta'));
    }
    
    public function addJsFile($src)
    {
        $fileId = 'head.jsfile.'.sha1($src);
        if (!empty($src) && !$this->repo->keyExists($fileId)) {
            if ($src[0] != '/' && (strpos($src,'http') !== 0)) {
                $src = OSY_WEB_ROOT.'/'.$src;
            }
            $this->set($fileId, $src);
        }
        return $this;
    }
    
    public function addJsCode($code)
    {
        $codeId = 'body.jscode.'.sha1($code);
        if (!empty($code) && !$this->repo->keyExists($codeId)) {
            $this->set($codeId, $code);
        }
        return $this;
    }
    
    public function getBody()
    {
        return $this->get('section.body');
    }
    
    public function getHead()
    {
        return $this->get('section.head');
    }
    
    public function render()
    {
        $this->sendHeader();
        $html = new Tag('html');
        $sections = $this->get('section');
        foreach ($sections as $section => $tagSection) {
            if ($sectionContent = $this->get($section)) {
                foreach ($sectionContent as $key => $elems) {
                    foreach ($elems as $elem) {
                        if (empty($elem)) {
                            continue;
                        }
                        switch ($key) {
                            case 'jsfile':
                                $tagSection->add(new Tag('script'))->att('src',$elem);
                                break;
                            case 'jscode':
                                $elem = trim($elem);
                                if ($elem[0] == '<'){
                                    $tagSection->add(PHP_EOL.$elem);
                                    break;
                                }  
                                $script = $tagSection->add(new Tag('script'));
                                $script->add($elem);
                                break;
                            case 'css':
                                $tagSection->add(new Tag('link'))->att(array('href'=>$elem,'rel'=>'stylesheet'));
                                break;
                            case 'error':
                                $tagSection->add(new Tag('div'))
                                           ->att('class', 'hidden')
                                           ->att('id', 'error')
                                           ->add(implode('<br>', $elem));
                                break;
                        }
                    }
                }
            }
            $html->add($tagSection);
        }        
        return '<!DOCTYPE '.$this->get('doctype').'>'.PHP_EOL.$html;
    }
    
    public function setTitle($title)
    {
        $this->get('section.head')
             ->add(new Tag('title'))
             ->add($title);
        return $this;
    }
    
    public function __toString()
    {
       return $this->render() . ($this->printMicrotime ? $this->printMicrotime() : '');
    }
    
    public function printMicrotime()
    {
        //echo TimeStart;
        $time = microtime(true) - TimeStart;
        return '<div id="microtime">'.($time)
               .' sec. - '.number_format(memory_get_usage(), 0, ',', '.').' byte'.'</div>';
    }
    
    public function error($oid=null, $err=null)
    {
        $this->repo->set('body.error.'.$oid, $err, true);
    }
}
