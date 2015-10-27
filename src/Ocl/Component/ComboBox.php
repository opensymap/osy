<?php
/**
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/ComboBox.php                                       |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create ComboBox component                                           |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   09/04/2015
 * @date-update     09/04/2015
 */
namespace Opensymap\Ocl\Component;

use Opensymap\Lib\Tag as tag;
use Opensymap\Ocl\Component\AbstractComponent;

//costruttore del combo box
class ComboBox extends AbstractComponent 
{
    private $value;
    private $datasource;
    
    public function __construct($nam,$id=null)
    {
        parent::__construct('select',nvl($id,$nam));
        $this->att('name',$nam);
    }

    protected function build()
    {
        $dataset = array();
        
        if (!empty($this->datasource)) {
            $dataset = is_array($this->datasource) ? 
                $this->datasource : 
                $this->datasource->get();
        } elseif ($dsr = $this->getParameter('datasource')) {
            $dataset = $dsr;
        } 
       
        if (!$this->getParameter('option-select-disable')) {
            if (!($lbl = $this->getParameter('label-inside'))) {
                $lbl = '- select -';
            }
            array_unshift($dataset, array('',$lbl));
        }
        
        $value = get_global($this->name, $_REQUEST);
        
        $idx = array(0,1);
        
        if ($this->getParameter('fields-order')) {
            $idx = explode(',',$this->getParameter('fields-order'));
        }
        
        foreach ($dataset as $k => $item) {
            //If is a tree combo datasource pass element's level
            $level = empty($item['__groupedLevel']) ? 0 : $item['__groupedLevel'];
            $item = array_values($item);
            if ($item[0] === false) {
                continue;
            }
            $sel = ($value == $item[$idx[0]]) ? ' selected' : '';
            $option = $this->add(new Tag('option'))
                           ->att('value',$item[$idx[0]]);
            $label = nvl($item[$idx[1]], $item[$idx[0]]);
            if ($level > 0) {
                $label = str_repeat('&nbsp;', $level * 4) . $label;
            }
            $option->add($label);
            if ($value == $item[$idx[0]]) {
                $option->att('selected','selected');
            }
        }
    }

    public function setDatasource($datasource)
    {
        $this->datasource = $datasource;
    }
}
