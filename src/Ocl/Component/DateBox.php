<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/DateBox.php                                        |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create  datebox component                                           |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   28/08/2013
 * @date-update     28/08/2013
 */
 
namespace Opensymap\Ocl\Component;

use Opensymap\Osy as env;
use Opensymap\Ocl\Component\AbstractComponent;
use Opensymap\Ocl\Component\TextBox;

class DateBox extends AbstractComponent
{
    public $dateField;
    
    public function __construct($nam, $id = null)
    {
        parent::__construct('div',$nam,nvl($id,$nam));
        $this->att('class','osy-datebox');
        $this->dateField = $this->add(new TextBox($nam))
                                ->att('placeholder','__/__/_____')
                                ->att('readonly')
                                ->att('size',8)
                                ->att('maxlength',12);
        $this->add('<span class="fa fa-calendar"></span>');
        $this->addRequire('js/component/DateBox.js');
    }
    
    protected function build()
    {
        $val = get_global($this->id,$_REQUEST);
        if (!empty($val)) {  
            $_REQUEST[$this->id] = $val; 
        }
        if (!empty($_REQUEST[$this->id]) && $this->get_par('date-format')) {
            if (strlen($_REQUEST[$this->id]) > 10) {
                list($data,$ora) = explode(' ',$_REQUEST[$this->id]);
                $adat = explode('-', $data);
            } else {
                $adat = explode('-', $_REQUEST[$this->id]);
            }
            if (count($adat) == 3) {
               $_REQUEST[$this->id] = str_replace(array('yyyy','mm','dd'), $adat, $this->get_par('date-format'));
            }
        }
        $this->dateField->att('value',$_REQUEST[$this->id]);
    }
    
    public static function convert($d,$df='dd/mm/yyyy')
    {
        if (!empty($d) && !empty($df)) {
            $adat = explode('-',$d);
            if (count($adat) == 3) {
                return str_replace(array('yyyy','mm','dd'), $adat, $df);
            }
        }
        return $d;
    }
}
