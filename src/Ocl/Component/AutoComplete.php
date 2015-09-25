<?php
/*
 +-----------------------------------------------------------------------+
 | core/Component/AutoComplete.php                                       |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create AutoComplete component                                       |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   09/06/2014
 * @date-update     09/06/2014
 */
namespace Opensymap\Ocl\Component;

use Opensymap\Driver\DboHelper;
use Opensymap\Ocl\AjaxInterface;

 /*
 * Autocomplete component
 */
class AutoComplete extends AbstractComponent implements AjaxInterface
{
    use DboHelper;
    
    public function __construct($name)
    {
        parent::__construct('input',$name);
        $this->att('type','text');
        $this->att('name',$name);
        $this->att('class','autocomplete');
        $this->addRequire('js/component/AutoComplete.js');
    }
    
    public function ajaxResponse($controller, &$response)
    {
        $sql = $this->replacePlaceholder($this->get_par('datasource-sql'));
        $res = $controller->model->dba->exec_query($sql,null,'ASSOC');
        $response->message('result',$res);
    }
    
    public function build()
    {
        $this->att('ops',$_REQUEST['ajax']);
        $val = get_global($this->id,$_REQUEST);
        if (!empty($val)) {
            $this->att('value',$val);
        }
    }
}