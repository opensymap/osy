<?
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/MapGridOpenLayers.php                              |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create  map openlayers component                                    |
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
 
class MapGridOpenLayers extends AbstractComponent
{
    private $map;
    private $cnt;
    
    public function __construct($name)
    {
        parent::__construct('dummy',$name);
        $this->addRequire('Ocl/Component/MapGridOpenLayers/controller.js');
        $this->map = $this->add(tag::create('div'))->att('class','osy-mapgrid-openlayers');
        $this->add(new HiddenBox($this->id.'_ne_lat'));
        $this->add(new HiddenBox($this->id.'_ne_lng'));
        $this->add(new HiddenBox($this->id.'_sw_lat'));
        $this->add(new HiddenBox($this->id.'_sw_lng'));
        $this->add(new HiddenBox($this->id.'_center'));
    }
    
    public function build()
    {
        foreach($this->get_att() as $k => $v)
        {
            if (is_numeric($k)) continue;
            $this->map->att($k,$v,true);
        }
        if (empty($_REQUEST[$this->id.'_center']) && $sql = $this->get_par('datasource-sql'))
        {
            $sql = env::replacevariable($sql);
            $res = env::$dba->exec_query($sql);
            if (empty($res)){ $res = array(array('lat'=>41.9100711,'lng'=>12.5359979)); }
            $_REQUEST[$this->id.'_center'] = $res[0]['lat'].','.$res[0]['lng'];
        }
        if ($grid = $this->get_par('datagrid-parent'))
        {
            $this->map->att('data-datagrid-parent',$grid);
        }
    }
}