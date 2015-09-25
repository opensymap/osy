<?php
/*
 +-----------------------------------------------------------------------+
 | core/Ocl/Component/Calendar.php                                       |
 |                                                                       |
 | This file is part of the Opensymap                                    |
 | Copyright (C) 2005-2013, Pietro Celeste - Italy                       |
 | Licensed under the GNU GPL                                            |
 |                                                                       |
 | PURPOSE:                                                              |
 |   Create Calendar component                                           |
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

use Opensymap\Osy as env;
use Opensymap\Lib\Tag;
use Opensymap\Driver\DboAdapterInterface;
use Opensymap\Driver\DboHelper;
use Opensymap\Ocl\AjaxInterface;
use Opensymap\Ocl\Component\AbstractComponent;
use Opensymap\Ocl\Component\HiddenBox;

class Calendar extends AbstractComponent implements DboAdapterInterface,AjaxInterface
{
    use DboHelper;
    
    private $db;
    private $items = array();
    private $days_of_week = array('Luned&igrave;',
                                  'Marted&igrave;',
                                  'Mercoled&igrave;',
                                  'Gioved&igrave;',
                                  'Venerd&igrave;',
                                  'Sabato',
                                  'Domenica');

    private $LMonth = array('01' => 'Gennaio',
                            '02' => 'Febbraio',
                            '03' => 'Marzo',
                            '04' => 'Aprile',
                            '05' => 'Maggio',
                            '06' => 'Giugno',
                            '07' => 'Luglio',
                            '08' => 'Agosto',
                            '09' => 'Settembre',
                            '10' => 'Ottobre',
                            '11' => 'Novembre',
                            '12' => 'Dicembre');

    public function __construct($init_date,$type=null)
    {
        $this->addRequire('css/CalendarMain.css');
        $this->addRequire('js/component/CalendarMain.js');
        parent::__construct('div');

        try {
            $idat = new \DateTime(empty($_REQUEST['calendar_init_date']) ? date('Y-m-d') : $_REQUEST['calendar_init_date']);
        } catch (\Exception $e){
            $idat = new \DateTime(date('Y-m-d'));
        }

        $this->__par = array('type' => 'mounthly',
                           'dimension'=>array('width'  => 640, 'height' => 480),
                           'days' => array(),
                           'type' => (empty($_REQUEST['calendar_layout']) ? 'mounthly' : $_REQUEST['calendar_layout']),
                           'init-date' => $idat);

        $this->att('class',"osy-view-calendar");
          
        switch($this->get_par('type')) {
            case 'daily': 
                $this->par('period',array($idat->format('Y-m-d'),$idat->format('Y-m-d')));
                $this->par('build-method','build_daily');
                break;
            case 'weekly': 
                $dw = $idat->format('w');
                $dw = empty($dw) ? 7 : $dw;
                //$ws = $idat->format('d') - ($dw - 1);
                //$we = $idat->format('d') + (7 - $dw);
                $dfws = clone $idat;
                $dfwe = clone $idat;
                $dfws->sub(new \DateInterval('P'.($dw - 1).'D'));
                $dfwe->add(new \DateInterval('P'.(7 - $dw).'D'));
                $this->par('period',array($dfws->format('Y-m-d'),$dfwe->format('Y-m-d')));
                $this->par('build-method','build_weekly');
                break;
            default     :
                $this->par('period',array($idat->format('Y-m-01'),$idat->format('Y-m-t')));
                $this->par('build-method','build_monthly');
                break;
        }
    }

    private function first_day_of_month($date)
    {
        list($aa,$mm,$dd) = explode('-',$date);
        return (($d = jddayofweek(GregorianToJD ($mm,1,$aa),0)) == 0) ? 7 : $d;
    }

    public function build()
    {
        if ($sql = $this->get_par('datasource-sql')) {
            $sql = $this->replacePlaceholder($sql);
            $this->set_datasource($sql , $this->db);
        }
        switch($this->get_par('type')) {
            case 'daily':
                $this->__build_daily__();
                break;
            case 'weekly':
                $this->__build_weekly__();
                break;
            default:     
                $this->__build_monthly__();
                break;
        }
    }
        
    private function __build_toolbar__($label,$prev,$next,$type='monthly')
    {
        $hd = $this->add(tag::create('div'))->att('class','osy-view-calendar-toolbar');
        //Button month navigation
        $nav = $hd->add(tag::create('div'))
                  ->att('class','osy-view-calendar-navigation');
        $nav->add('<input type="button" name="btn_prev" value="&lt;" class="osy-calendar-command" data-date="'.($prev->format('Y-m-d')).'">');
        $nav->add('<input type="button" name="btn_next" value="&gt;" class="osy-calendar-command" data-date="'.($next->format('Y-m-d')).'">');
        //Label current month
        $lbl = $hd->add(tag::create('div'))->att('class','osy-view-calendar-label')->add($label);
        //Button calendar type
        $dty = $hd->add(tag::create('div'))->att('class','osy-view-calendar-type');
        $dty->add('<input type="submit" id="btn_daily" value="Giorno" class="osy-calendar-command'.($type=='daily' ? ' ui-state-active' : '').'">');
        $dty->add('<input type="submit" id="btn_weekly" value="Settimana" class="osy-calendar-command'.($type=='weekly' ? ' ui-state-active' : '').'">');
        $dty->add('<input type="submit" id="btn_monthly" value="Mese" class="osy-calendar-command'.($type=='monthly' ? ' ui-state-active' : '').'">');
        $hd->add('<br style="clear: both;">');
    }
        
    public function __build_daily__()
    {
        $prev = new \DateTime($this->get_par('init-date')->format('Y-m-d'));
        $next = new \DateTime($this->get_par('init-date')->format('Y-m-d'));
        $prev->sub(new \DateInterval('P1D'));
        $next->add(new \DateInterval('P1D'));
        $this->__build_toolbar__($this->get_par('init-date')->format('d F Y'),$prev,$next,'daily');
        $calendar = $this->add(tag::create('div'))->att('class','osy-view-calendar-daily');
        $body_noh = $calendar->add(tag::create('div'))->att('class','daily-events');
        $body = $calendar->add(tag::create('div'))->att('class','timed-events')->add(tag::create('table'));
        $curd = $this->_current_day.'/'.$this->__par__['init-month'].'/'.$this->__par__['init-year'];
        $devt = (!empty($this->items[$curd])) ? $this->items[$curd] : array();
        //var_dump($this->items);
        $raw_items = array_key_exists($this->get_par('init-date')->format('Y-m-d'),$this->items) ? $this->items[$this->get_par('init-date')->format('Y-m-d')] : array();
        $items = array();
        foreach($raw_items as $rec) {
            $a = explode(':',$rec['hour']);
            if (intval($a[0]) < 8) {
                $items[0][] = $rec;
            } else {
                $min = empty($a[1]) || intval($a[1]) < 30 ? '00' : '30';
                $items[intval($a[0])][$min][] = $rec;
            }
        }
        
        $format_item = function($items, $class='', $hour='') 
        {
            $td = tag::create('td')->att('class','event-cont add_event '.$class);
            if (empty($items)){  $td->add('&nbsp;'); return $td; }
            $pkey = $this->get_par('pkey');
                
            foreach($items as $k => $rec) {
                if (empty($rec)) continue;
                $div = $td->add(tag::create('div'))
                          ->att('class','event '.(!empty($rec['event_color']) ? $rec['event_color'] : 'osy-event-color-normal'));
                if (!empty($rec['hour'])) {
                    $end = $rec['event_end'] ? " &#8594; ".$rec['event_end'] : '';
                    $div->add("<span class=\"event-time\">{$rec['hour']}{$end}</span>");
                } elseif(!empty($rec['event_duration'])) {
                    $div->add("<span class=\"event-time\">{$rec['event_duration']} min</span>");
                }
                $itm = $div->add(tag::create('div'))->att('class','event-body');
                $itm->add($rec['event']);
                if (is_array($pkey)) {
                    $key = array();
                    foreach($pkey as $k => $fld) {
                        if (array_key_exists($fld,$rec)) $key[] = 'pkey['.$fld.']='.$rec[$fld];
                    }
                    if (count($pkey) == count($key)) {
                        $itm->att('__k',implode('&',$key))->att('class','osy-view-calendar-item',true);
                    }
                }
            }
            return $td;
        };
        
        $table_head = $body_noh->add(tag::create('table'));
        $row = $table_head->add(tag::create('tr'));
        $row->add(tag::create('td'))->att('class', 'dummy-time')->add('<a href="#" class="add_event">+ Evento</a>');
        if (!empty($items[0])){             
            $row->add($format_item($items[0],'event-daily'));
        } else {
            $row->add($format_item(array(array()),'event-daily'));
        }
        foreach(range(0,23) as $v) {
            $row = $body->add(tag::create('tr'));
            $chh = $row->add(tag::create('td'))
                       ->att('class', 'cont-hour')
                       ->att('rowspan','2');
            $hh = str_pad($v,2,'0',STR_PAD_LEFT).':00';
            $chh->add($hh);
            $cnt = $row->add($format_item($items[$v]['00'],'btop-solid')->att('data-hour',$hh));
            if ($v == 8) {
                $chh->att('class','dummy-first',true);
            }
            $row = $body->add(tag::create('tr'));
            $cnt = $row->add($format_item($items[$v]['30'],'btop-dot')->att('data-hour',str_replace(':00',':30',$hh)));
        }
    }

    private function __build_weekly__(){
        $start_day = $this->get_par('init-date')->format('Y-m-d');
        $intv = new \DateInterval('P1W');
        $prev = new \DateTime($this->get_par('init-date')->format('Y-m-d'));
        $prev->sub($intv);
        $next = new \DateTime($this->get_par('init-date')->format('Y-m-d'));
        $next->add($intv);

        //Calcolo primo giorno della settimana.
        $week_day = $prev->format('w') == '0' ? 7 : $prev->format('w');
        $current_day = new \DateTime($this->get_par('init-date')->format('Y-m-d'));
        $current_day->sub(new \DateInterval('P'.($week_day-1).'D'));
        $last_day = new \DateTime($current_day->format('Y-m-d'));
        $last_day->add(new \DateInterval('P7D'));
        $label = $current_day->format('d') . ' - ' . $last_day->format('d M Y');
        
        if ($current_day->format('Y') < $last_day->format('Y')){
            $label = $current_day->format('d M Y') . ' - ' . $last_day->format('d M Y');
        } elseif ($current_day->format('d') > $last_day->format('d')){
            $label = $current_day->format('d M') . ' - ' . $last_day->format('d M Y');
        } else {
            $label = $current_day->format('d') . ' - ' . $last_day->format('d M Y');
        }
        
        $this->__build_toolbar__($label,$prev,$next,'weekly');
        
        $calendar = $this->add(tag::create('div'))->att('class','osy-view-calendar-weekly');
        //HEAD
        $head = $calendar->add(tag::create('div'))
                         ->att('id','calendar-head')
                         ->att('class','osy-view-calendar-head');
        $rw1 = $head->add(tag::create('div'))->att('class','day-label');
        $rw1->add(tag::create('div'))->att('id','dummy-event')->add('<span>&nbsp;</span>');
        $rw2 = $head->add(tag::create('div'))->att('class','day-event');
        $rw2->add(tag::create('div'))->att('id','dummy-event')->add('<span>&nbsp;</span>');
        $items = array();
        
        foreach($this->items as $day => $events) {
            foreach($events as $k => $rec) {
                $a = explode(':',$rec['hour']);
                //var_dump($rec);
                if (intval($a[0]) < 8){
                    $items[$day][0][] = $rec;
                } else {
                    $min = empty($a[1]) || intval($a[1]) < 30 ? '00' : '30';
                    $items[$day][intval($a[0])][$min][] = $rec;
                }
            }
        }
        $pkey = $this->get_par('pkey');
        foreach (range(1,7) as $k => $v) {
            $rw1->add(tag::create('div'))
                ->att('class','day-num')
                ->att('data-date',$current_day->format('Y-m-d'))
                ->add($current_day->format('D d/m'));
            $div = $rw2->add(tag::create('div'));
            if (!empty($items[$current_day->format('Y-m-d')][0])) {
                $this->__make_item__($div,$items[$current_day->format('Y-m-d')][0]);
            } 
            $current_day->add(new \DateInterval('P1D'));
        }
        //BODY
        $body = $calendar->add(tag::create('div'))
                         ->att('id','calendar-body')
                         ->add(tag::create('table'))
                         ->att('class','osy-view-calendar-body')
                         ->add(tag::create('tbody'));
        $cgr = $body->add(tag::create('colgroup'));
        $cgr->add(tag::create('col'))->att('class','col-hour');
        $cgr->add(tag::create('col'))->att('span',7)->att('class','col-event');
        foreach(range(0,23) as $v) {
            $hh = str_pad($v,2,'0',STR_PAD_LEFT);
            $row_1 = $body->add(tag::create('tr'));
            $row_2 = $body->add(tag::create('tr'));
            $chh = $row_1->add(tag::create('td'))
                         ->att('class', 'cont-hour btop-solid')
                         ->att('rowspan','2');
            $chh->add($hh.':00');
            if ($v == 8){ $chh->att('class','dummy-first',true); }
            $current_day = new \DateTime($this->get_par('init-date')->format('Y-m-d'));
            $current_day->sub(new \DateInterval('P'.($week_day-1).'D'));
            foreach(range(1,7) as $v){
                $cel_1 = $row_1->add(tag::create('td'))->att('class','btop-solid add_event')->att('data-hour',$hh.':00');
                $cel_2 = $row_2->add(tag::create('td'))->att('class','btop-dot add_event')->att('data-hour',$hh.':30');
                $day = $current_day->format('Y-m-d');
                if ($items[$day] && !empty($items[$day][intval($hh)])){
                    foreach(array('00','30') as $half) {
                        if($half == '00') {
                          $cel =& $cel_1;
                        } else {
                          $cel =& $cel_2;
                        }
                        if (!empty($items[$day][intval($hh)][$half])) {
                            $this->__make_item__($cel,$items[$day][intval($hh)][$half]);
                        } else  {
                            $cel->add('&nbsp;');
                        }
                    }
                } else {
                    $cel_1->add('&nbsp;');
                    $cel_2->add('&nbsp;');
                }
                $current_day->add(new \DateInterval('P1D'));
            }
         }
    }
    
    private function __build_monthly__()
    {
        $days = array_pad(array(),43,"&nbsp;");
        $month_len = $this->get_par('init-date')->format('t');
        $start_day = $this->first_day_of_month($this->get_par('init-date')->format('Y-m-d'));
        //var_dump($start_day);
        for ($i = 0; $i < $month_len; $i++) {
            $days[$start_day + $i] = $i + 1;
        }
        $cell_hgt = floor(($this->__par['dimension']['height'] - 120) / 6);
        $cell_wdt  = floor($this->__par['dimension']['width'] / 7)-2;
        $intv = new \DateInterval('P1M');
        $prev = new \DateTime($this->get_par('init-date')->format('Y-m-01'));
        $prev->sub($intv);
        $next = new \DateTime($this->get_par('init-date')->format('Y-m-01'));
        $next->add($intv);
        //Build toolbar;
        $this->__build_toolbar__($this->get_par('init-date')->format('F Y'),$prev,$next);
        //Build body;
        $body = $this->add(tag::create('table'))->att('class','osy-view-calendar-monthly osy-maximize');
        $row = $body->add(tag::create('thead'))->add(tag::create('tr'));
        foreach($this->days_of_week as $day) {
            $row->add(tag::create('th'))->add($day);
        }
        $k        = $h = 1;
        $data     = $this->get_par('init-date')->format('Y-m-');
        $tbody    = $body->add(tag::create('tbody'));
        $today    = date('Y-m-d');
        $init_day     = $this->get_par('init-date')->format('Y-m-d');
        for ($j = 0; $j < 6; $j++) {
            $row = $tbody->add(tag::create('tr'));
            for ($i = 0; $i < 7; $i++) {
                $cel = $row->add(tag::create('td'))
                           ->att('class','day')
                           ->att('valign','top')
                           ->att('style',"height: {$cell_hgt}px; width: {$cell_wdt}px;");
                switch($i) {
                    case 6:
                            $cel->style .= 'color: red;';
                            break;
                }
                if ($days[$k] == "&nbsp;") {
                    $cel->att('class','dummy')->add('&nbsp');
                } else {
                    $value='';
                    $data_ciclo = $data.str_pad($days[$k],2,'0',STR_PAD_LEFT);
                    $cel->att('data-date',$data_ciclo)
                        ->att('onmousedown',"return false")
                        ->att('onselectstart',"return false");
                    
                    if ($data_ciclo == date('Y-m-d')) $cel->att('class','today',true);
                    if ($data_ciclo == $init_day) $cel->att('class','selected',true);
                    //Num day
                    $cel->add(tag::create('div'))
                        ->att('class',"day-num")
                        ->add($days[$k]);
                    if (array_key_exists($data_ciclo,$this->items)) {
                        if (!empty($this->items[$data_ciclo])) {
                            $cnt = $cel->add(tag::create('div'))->att('class','cell-cont '.($data_ciclo < date('Y-m-d') ? 'day-past' : 'day-future'),true);
                            $cnt->att('style','width: '.$cell_wdt.'px;');
                            $ext_evt = 0;
                            for ($t = 0; $t < count($this->items[$data_ciclo]); $t++) {
                                if ($t>1) { 
                                   $ext_evt++; 
                                   continue; 
                                }
                                if (!empty($this->items[$data_ciclo][$t])) {
                                    $item = $this->items[$data_ciclo][$t];
                                    $cnt->add(tag::create('div'))
                                        ->add('<span>'.$item['hour'].'</span>'.$item['event.short']);
                                }
                            }
                            if (!empty($ext_evt)) {
                                $cnt->add(tag::create('div'))->add(($ext_evt == 1 ? '+ un altro evento' : '+ altri '.$ext_evt.' eventi.'));
                            }
                        }
                    }
                }
                $k++;
            }
        }
    }

    private function __make_item__($par,$items,$class='',$hour=''){
        $pkey = $this->get_par('pkey');
        foreach($items as $k => $rec) {
            if (empty($rec)) continue;
            $div = $par->add(tag::create('div'))
                      ->att('class','event '.(!empty($rec['event_color']) ? $rec['event_color'] : 'osy-event-color-normal'));
            if (!empty($rec['hour'])) {
                $end = $rec['event_end'] ? " &#8594; ".$rec['event_end'] : '';
                $div->add("<span class=\"event-time\">{$rec['hour']}{$end}</span>");
            } elseif(!empty($rec['event_duration'])) {
                $div->add("<span class=\"event-time\">{$rec['event_duration']} min</span>");
            }
            $itm = $div->add(tag::create('div'))
                       ->att('class','event-body');
            $itm->add($rec['event']);
            if (is_array($pkey)) {
                $key = array();
                foreach($pkey as $k => $fld) {
                    if (array_key_exists($fld,$rec)) $key[] = 'pkey['.$fld.']='.$rec[$fld];
                }
                if (count($pkey) == count($key)) {
                    $itm->att('__k',implode('&',$key))->att('class','osy-view-calendar-item',true);
                }
            }
        }
        return $itm;
    }
     
    public function GetEvent($dat)
    {
        return $this->items[$dat];
    }

    public function set_datasource($sql,$db)
    {
        if (empty($sql)) return;
        $period = $this->get_par('period');
        $sql = "SELECT a.* FROM ({$sql}) a 
                WHERE a.day 
                BETWEEN str_to_date('{$period[0]} 00:00:00','%Y-%m-%d %H:%i:%s') 
                    AND str_to_date('{$period[1]} 23:59:59','%Y-%m-%d %H:%i:%s')";
        $rs = $db->exec_query($sql,null,'ASSOC');
        foreach ($rs as $rec)
        {
           $this->push_event($rec);
        }
    }

    public function set_dimension($w,$h)
    {
        if (!empty($w)) $this->__par['dimension']['width'] = $w;
        if (!empty($h)) $this->__par['dimension']['height'] = $h;
    }

    public function push_event($rec)
    {
        $err = false;
        $par = array('day','hour','event');
        $evt = array();
        foreach($par as $key) {
            if (!array_key_exists($key,$rec)) {
                die("La query di estrazione dati non contiene il campo ".$key);
            }
        }
        $rec['event.short'] = strlen($rec['event']) > 18 ? substr($rec['event'],0,16).'..' : $rec['event'];
        $this->items[$rec['day']][] = $rec;
    }
    
    public function ajaxResponse($controller, &$response)
    {
    }
    
    public function setDboHandler($db)
    {
        $this->db = $db;
    }
}