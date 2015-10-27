<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/Chart.php                                          |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create Chart component                                              |
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

use Opensymap\Lib\Tag;
use Opensymap\Helper\HelperOsy;
use Opensymap\Ocl\AjaxInterface;
use Opensymap\Ocl\Component\AbstractComponent;
use Opensymap\Ocl\Component\HiddenBox;


class Chart extends AbstractComponent implements AjaxInterface
{
    private $__color__ = array(
        '#F38630',
        '#4D5360',
        '#949FB1',
        '#69D2E7',
        '#E0E4CC'
    );
    private $canvas;
    private $legend;
    private $cnt_id;
    private $db;
    private $datasource;
    
    public function __construct($id)
    {
        parent::__construct('div', $id);
        $this->addRequire('Ocl/Component/Chart/style.css');
        $this->addRequire('Ocl/Component/Chart/ChartNew.js');
        $this->addRequire('Ocl/Component/Chart/controller.js');
        $this->att('class','osy-graph');
    }

    public function build()
    {
        $val = array();

        $this->buildTabs();

        $this->canvas = $this->add(tag::create('canvas'));

        foreach ($this->getAtt() as $k => $att) {
            if ($k=='0' || $k=='style') {
                continue;
            }
            $this->canvas->att($k,$att);
        }

        $this->canvas
             ->att('id',$this->id.'_canvas')
             ->att('class','osy-graph-canvas')->add(' ');
        $this->att('style','width: 97.5%;',true);
        $this->legend = $this->add(tag::create('table'));
        $this->legend->att('class','float-left');
        $js  = '';
        $opt = array();
    }
    
    public function ajaxResponse($controller, &$response)
    {
        if (!empty($this->datasource)) {
            $val = is_array($this->datasource) ? $this->datasource : $this->datasource->get();
        } 

        if (empty($val)) {
          $this->add("<p style=\"padding: 33px 0px; text-align: center; font-weight: bold;\">Impossibile costruire il grafico. Non ci sono dati.</p>");
          return;
        }
        
        $tot = 0;

        foreach ($val as $k => $rec) {
            $tot += array_key_exists(1,$rec) ? $rec[1] : 0;
        }

        $datasets = array();

        foreach ($val as $k => $rec) {
            foreach ($rec as $j => $v) {
                $datasets[$j][] = empty($v) ? '0' : $v;
            }
        }
        
        switch ($this->getParameter('graph-type')) {
            case 'pie':
                $data = $this->buildPie($datasets);
                break;
            case 'bar':
                $data = $this->buildBar($datasets);
                break;
            case 'line':
                $data = $this->buildLine($datasets);
                break;
        }
        
        $response->set('content.dataset', $data);
    }
    
    private function buildTabs()
    {
        if (!($tab = $this->getParameter('graph-tab'))) return;
        $atab = explode(',',$tab);
        if (empty($_REQUEST[$this->id])){ 
            $_REQUEST[$this->id] = $atab[0];
        }
        $this->add(new HiddenBox($this->id,$this->id.'_tabs'));
        $tabs = $this->add(tag::create('ul'))->att('class','osy-graph-tabs');
        foreach ($atab as $k => $v) {
            $tabs->add(tag::create('li'))->att('href','#')->att('class','osy-graph-tab'.($_REQUEST[$this->id] == $v ? ' osy-graph-tab-sel' : ''))->add($v);
        }
    }

    private function buildPie($datasets)
    {
        $js .= (empty($js) ? '' : ",\n")."{value : ".(array_key_exists(1,$rec) ? $rec[1] : 0).",color : '".(!empty($this->_color[$k]) ? $this->_color[$k] : '')."'}";

        $tr = $this->legend->add(tag::create('tr'));
        $tr->add(tag::create('td'))->att('style','padding: 5px;')
           ->add('<span style="background-color: '.$this->_color[$k].'; padding: 0px 5px; border-radius: 1px; border: 2px solid whitesmoke;";>&nbsp;</span>');
        $tr->add(tag::create('td'))->att('style','padding: 5px;')
           ->add($rec[0]);
        $tr->add(tag::create('td'))->att('style','padding: 5px;')
           ->add($rec[1]);
        $tr->add(tag::create('td'))->att('style','padding: 5px;')
           ->add(round($rec[1] / $tot * 100,2).' %');
         $js = "var data = [\n" . $js . "\n];".PHP_EOL;
         $js .= "var ctx = document.getElementById('".$this->canvas->id."_canvas').getContext('2d');
            var chr = new Chart(ctx).Pie(data);";
         return;
    }

    private function buildLine($datasets)
    {
             $str_datasets = '';
             $opt = null;
             $labels = array_shift($datasets);
             foreach ($datasets as $k => $data) {
                $str_datasets = (!empty($str_datasets) ? "," : '').
                    "{fillColor : 'rgba(220,220,220,0.5)',
                      strokeColor : 'rgba(220,220,220,1)',
                      pointColor : 'rgba(220,220,220,1)',
                      pointStrokeColor : '#fff',
                      data : [".implode(',',$data)."]}";
             }
             $js = "var data = {labels : {$labels},
                                datasets : [$str_datasets]}
                    var ctx = document.getElementById('".$this->canvas->id."').getContext('2d');
                    var chr = new Chart(ctx).Line(data".(empty($opt) ? '' : ',{'.implode(',',$opt).'}').");";
             return $js;
    }

    private function buildBar($raw_data)
    {
        $lbl = array_shift($raw_data);
        $opt = array(
            'inGraphDataShow'=> true,
            'inGraphDataYPosition' => 3,
            'scaleFontSize'=> 9,
            'inGraphDataFontSize' => 9,
            'yAxisMinimumInterval' =>1,
            'responsive' => true,
            'maintainAspectRatio' => false,
            'spaceLeft'=>20,
            'spaceRight'=>20,
            'spaceTop'=>10,
            'spaceBottom' => 10,
            'legend' => (
                count($raw_data)>1 ? true : false
            ),
            'legendBorders' => false
        );
        if (!$this->getParameter('hide-title')) {
            $opt['graphTitle'] = $this->label;
        }
        if ($onmousedownleft = $this->getParameter('chartnew-mousedownleft')) {
            $opt['mouseDownLeft'] = 'function(event,ctx,config,data,other) { '.PHP_EOL.$onmousedownleft.PHP_EOL.'}';
            $opt['annotateDisplay'] = true;
        }
        if ($fmt = $this->getParameter('ingraphdatatmpl')) {
            $dataset = array_values($raw_data)[0];
            $fmt = HelperOsy::replaceVariable($fmt,$dataset,'\[(.*)\]');
            $opt['inGraphDataTmpl'] = '\'<%='.str_replace("'",'"',$fmt).'%>\'';
        }
        $dat = $this->buildDataset($raw_data);
        
        return array(array('labels'=>$lbl,'datasets'=>$dat),$opt);
    }

    private function buildDataset($raw_data)
    {
        $datasets = array();
        $colors = $this->getParameter('chartnew-graph-color') ? 
            explode(',',$this->getParameter('chartnew-graph-color')) : 
            $this->__color__;

        switch (count($raw_data)) {
           case 0:
                break;
           case 1:
                $colors = array($colors);
                break;
           default:
                $colors = array_pad($colors,count($raw_data),'ddd');
                break;
        }

        $i = 0;
        foreach ($raw_data as $title => $data) {
            $app = array(
                'fillColor'   => $colors[$i],
                'strokeColor' => 'rgba(220,220,220,1)',
                'pointColor'  => 'rgba(220,220,220,1)',
                'pointStrokeColor' => '#fff',
                'data' => $data,
                'title' => $title
            );
            $datasets[] = $app;
            $i++;
        }

        return $datasets;
    }

    private function calcShade($n)
    {
        $col = array();
        $prc = ceil(255 / $n);
        $sta = array(0,20,77);

        for($i = 0; $i < $n; $i++) {
            $col1 = 255 - ($sta[0] + ($i*$prc));
            $col2 = 255 - ($sta[1] + ($i*$prc));
            $col3 = 255 - ($sta[2] + ($i*$prc));
            $col[] = "'rgba(".$col1.",".$col2.",".$col3.",0.7)'";
        }

        return "[".implode(",",$col)."]";
    }

    private function calcRandomColor ($n)
    {
        $col = array();
        for ($i = 0; $i < $n; $i++) {
            $col1 = rand(0,255);
            $col2 = rand(0,255);
            $col3 = rand(0,255);
            $col[] = "'rgba(".$col1.",".$col2.",".$col3.",0.5)'";
        }
        return "[".implode(",",$col)."]";
    }

    
    public function setDatasource($ds)
    {
        $this->datasource = $ds;
    }
}
