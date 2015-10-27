<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl7Component/MapGridLeaflet.php                                 |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Leaflet mapgrid component                                           |
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
use Opensymap\Lib\Tag;
use Opensymap\Ocl\Component\AbstractComponent;
use Opensymap\Ocl\Component\HiddenBox;
use Opensymap\Driver\DboAdapterInterface;
use Opensymap\Driver\DboHelper;

class MapGridLeaflet extends AbstractComponent implements DboAdapterInterface
{
    use DboHelper;
    
    private $map;
    private $cnt;
    private $db;
    private $datasource;
    
    public function __construct($name)
    {
        parent::__construct('dummy',$name);
        $this->addRequire('Ocl/Component/MapGridLeaflet/style.css');
        $this->addRequire('Ocl/Component/MapGridLeaflet/styleDraw.css');
        $this->addRequire('/vendor/leaflet/leaflet.js');
        $this->addRequire('/vendor/leaflet/leaflet.awesome-markers.min.js');
        $this->addRequire('/vendor/leaflet/leaflet.draw.js');
        $this->addRequire('Ocl/Component/MapGridLeaflet/controller.js');
        $this->map = $this->add(new Tag('div'))
                          ->att('class','osy-mapgrid-leaflet');
        $this->add(new HiddenBox($this->id.'_ne_lat'));
        $this->add(new HiddenBox($this->id.'_ne_lng'));
        $this->add(new HiddenBox($this->id.'_sw_lat'));
        $this->add(new HiddenBox($this->id.'_sw_lng'));
        $this->add(new HiddenBox($this->id.'_center'));
        $this->add(new HiddenBox($this->id.'_cnt_lat'));
        $this->add(new HiddenBox($this->id.'_cnt_lng'));
        $this->add(new HiddenBox($this->id.'_zoom'));
    }
    
    public function build()
    {
        foreach ($this->getAtt() as $k => $v) {
            if (is_numeric($k)) {
                continue;
            }
            $this->map->att($k,$v,true);
        }
        if ($sql = $this->getParameter('datasource-sql')) {
            $sql = $this->replacePlaceholder($sql);
            $res = $this->db->exec_unique($sql,null,'ASSOC');
        }
        //var_dump($res);
        if (empty($res)) { 
            $res = array(
                'lat' => 41.9100711,
                'lng' => 12.5359979,
                'ico' => null
            );
        }
        $this->map->att(
            'coostart',
            $res['lat'].','.$res['lng'].','.$res['ico']
        );
        if (empty($_REQUEST[$this->id.'_center'])) {
            $_REQUEST[$this->id.'_center'] = $res['lat'].','.$res['lng'];
        }
        if ($grid = $this->getParameter('datagrid-parent')) {
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
