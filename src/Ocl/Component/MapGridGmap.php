<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/MapGridGmap.php                                    |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create page form for generate map google component                  |
 |                                                                       |
 +-----------------------------------------------------------------------+
 | Author: Pietro Celeste <pietro.celeste@gmail.com>                     |
 +-----------------------------------------------------------------------+

 $Id:  $

/**
 * @email           pietro.celeste@opensymap.org
 * @date-creation   31/10/2014
 * @date-update     31/10/2014
 */
namespace Opensymap\Ocl\Component; 

use Opensymap\Osy as env;
use Opensymap\Driver\DboAdapterInterface;
use Opensymap\Driver\DboHelper;
use Opensymap\Lib\Tag as Tag;

class MapGridGmap extends AbstractComponent implements DboAdapterInterface
{
    use DboHelper;
    
    private $db;
    private $map;
    private $cnt;
    private $datasource;
    
    public function __construct($name)
    {
        parent::__construct('dummy',$name);
        $this->addRequire('Ocl/Component/MapGridGmap/style.css');
        $this->addRequire('http://maps.google.com/maps/api/js?sensor=false&amp;language=en&libraries=drawing');
        $this->addRequire('/vendor/gmap3-6.0.0/gmap3.min.js');
        $this->addRequire('Ocl/Component/MapGridGmap/controller.js');
        $this->map = $this->add(tag::create('div'))->att('class','osy-mapgrid');
        $this->add(new HiddenBox($this->id.'_ne_lat'));
        $this->add(new HiddenBox($this->id.'_ne_lng'));
        $this->add(new HiddenBox($this->id.'_sw_lat'));
        $this->add(new HiddenBox($this->id.'_sw_lng'));
        $this->add(new HiddenBox($this->id.'_center'));
        $this->add(new HiddenBox($this->id.'_polygon'));
        $this->add(new HiddenBox($this->id.'_refresh_bounds_blocked'));
    }
    
    public function build()
    {
        foreach($this->getAtt() as $k => $v) {
            if (is_numeric($k)) {
                continue;
            } 
            $this->map->att($k,$v,true);
        }
        if (empty($_REQUEST[$this->id.'_center']) && $sql = $this->getParameter('datasource-sql')) {
            $sql = $this->replacePlaceholder($sql);
            $res = $this->db->exec_query($sql);
            if (empty($res)) { 
                $res = array(array('lat'=>41.9100711,'lng'=>12.5359979));   
            }
            $_REQUEST[$this->id.'_center'] = $res[0]['lat'].','.$res[0]['lng'];
        }
        if ($grid = $this->getParameter('datagrid-parent')){
            $this->map->att('data-datagrid-parent',$grid);
        }
    }
    
    public function setDboHandler($db)
    {
        $this->db = $db;
    }
    
    public function setDatasource($ds)
    {
        $this->datasource = $ds;
    }
}
