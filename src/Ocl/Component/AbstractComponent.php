<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/Component.php                                      |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Base class of component                                             |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   09/04/2005
 * @date-update     09/04/2005
 */

namespace Opensymap\Ocl\Component;

use Opensymap\Lib\Tag as Tag;

function nvl($a,$b)
{
    return ( $a !== 0 && $a !=='0' && empty($a) ) ? $b : $a;
}

function get_global($nam,$arr_val)
{
    if (strpos($nam,'[') === false) {
        return key_exists($nam,$arr_val) ? $arr_val[$nam] : '';
    }
    $arr_nam = explode('[',str_replace(']','',$nam));
    $res = false;
    foreach ($arr_nam as $nam) {
        if (!array_key_exists($nam,$arr_val)) {
            continue;
        }
        if (is_array($arr_val[$nam])) {
            $arr_val = $arr_val[$nam];
        } else {
            $res = $arr_val[$nam];
            break;
        }
    }
    return $res;
}


/**
 * 
 *
 * @author PIETRO CELESTE
 * @version 1.0
 */
abstract class AbstractComponent extends Tag
{
    protected $__par = array();
    protected $__evt = array();
    private $model;
    
    public $requires = array(
        1 => array(), 
        2 => array()
    );
    const JS = 1;
    const CSS = 2;
    
    public function __construct($tag, $id=null)
    {
        parent::__construct($tag, $id);
    }

    protected function _build()
    {
        $this->trigger('onbuild');
        if ($is_pk = $this->get_par('db-field-is-pkey')) {
            $this->att('class','osy-pkey',true)
                 ->att('osypk',$this->get_par('db-field-connected'));
        }
        $this->build();
        return parent::_build(-1);
    }
    
    abstract protected function build();

    public function get_par($key)
    {
        return array_key_exists($key,$this->__par) ? $this->__par[$key] : null;
    }

    public function man($mom, $par, $fnc) //Parameter manager
    {
        $this->__evt[$mom][$par] = $fnc;
    }

    public function par($key, $val = null, $fnc = null)
    {
        $this->__par[$key] = $val;
        if (is_callable($fnc)) {
            $fnc($key, $val, $this);
        }
        $this->trigger('oninsert',$key);
    }

    public function trigger($mom='onbuild',$par=null)
    {
        foreach (array_reverse($this->__par) as $k => $v) {
            if (!is_null($par) && $par != $k) {
                continue;
            }
            if (
                array_key_exists($mom,$this->__evt) && 
                is_array($this->__evt[$mom]) && 
                key_exists($k,$this->__evt[$mom])
            ) {
                $this->__evt[$mom][$k]($k, $v, $this);
            }
        }
    }

    public function appendRequired($response)
    {
        foreach ($this->requires as $k => $required) {
            foreach ($required as $val) {
                switch ($k) {
                    case 1:
                        $response->addJsFile($val);
                        break;
                    case 2:
                        $response->addCss($val);
                        break;
                }
            }
        }
    }
    
    protected function addRequire($path, $type=null)
    {
        if (empty($type)) {
            $type = strpos($path,'.css') === false ? self::JS : self::CSS;
        }
        $this->requires[$type][] = $path;
    }
    
    final public function getResponse()
    {
        return $this->model->response;
    }
    
    final public function getRequest($par)
    {
        return $this->model->request->get($par);
    }
    
    final public function getModel()
    {
        return $this->model;
    }
    
    final public function setModel(&$model)
    {
        $this->model =& $model;
    }
}
