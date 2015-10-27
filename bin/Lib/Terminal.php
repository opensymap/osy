<?php
namespace Installer\Lib;

class Terminal
{
    private $color = array();
    private $keyboard = null;
    
    public function __construct()
    {
        $this->color['green']  = "[42m"; //Green background
        $this->color['red']    = "[41m"; //Red background
        $this->color['yellow'] = "[43m"; //Yellow
        $this->color['blue']   = "[44m"; //Blue
        $this->keyboard = fopen("php://stdin","r");
    }
    
    public function label($text,$color='blue')
    {
        return chr(27) . $this->color[$color] . "$text" . chr(27) . "[0m";
    }
    
    public function input($label,$color='blue')
    {
        print $this->label($label,$color);
        $resp = fgets($this->keyboard,80);
        return $resp;
    }
}