<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/Button.php                                         |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create Button component                                             |
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

use Opensymap\Osy as env;
use Opensymap\Ocl\Component\AbstractComponent;

class Button extends AbstractComponent
{
    public function __construct($nam, $id=null, $typ='button') 
    {
        parent::__construct('button',nvl($id, $nam));
        $this->att('name',$nam)
             ->att('type',$typ)
             ->att('label','no-label');
        $this->par('no-label',true);
    }
    
    protected function build()
    {
        $this->add('<span>'.$this->label.'</span>');
        
        if ($formId = $this->getParameter('form-related')) {
            /*$formPar = (array_key_exists('rel_fields',$this->__par)) ? explode(',',$this->__par['rel_fields']) : array();
            $strPar = array();
            foreach($formPar as $field) { 
                $strPar[] = $field.'='.get_global($field,$_REQUEST);
            }*/
            
            $this->att('onclick', "oform.command.open_window64(this,'form-related')");
            if ($var = $this->getParameter('variables-child-post')) {
                 $this->att('variables-child-post',$var);
            }
        }
    }
}
