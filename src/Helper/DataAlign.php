<?php
namespace Opensymap\Helper;


class DataAlign
{
    private $data_source = array();
    private $data_dest = array();
    private $__dbs = null;
    private $__dbd = null;
    private $data_insert = array();
    private $data_update = array();
    private $data_delete = array();
    public  $message = '';
    private $time_start;
    
    public function __construct()
    {
        $this->time_start = microtime(true);
    }
    
    public function rebuild_data($dat)
    {
        $app = array();
        foreach($dat as $rec) {
            $app[$rec[0]] = $rec;
        }
        return $app;
    }
    
    public function load_data_source($db,$sql,$par=null)
    {
        $this->__dbs = $db;
        $dat = $this->__dbs->exec_query($sql,$par,'NUM');
        $this->data_source = $this->rebuild_data($dat);
    }
    
    public function load_data_dest($db,$sql,$par=null)
    {
        $this->__dbd = $db;
        $dat = $this->__dbd->exec_query($sql,$par,'NUM');
        $this->data_dest = $this->rebuild_data($dat);
    }
    
    public function prepare()
    {
        foreach($this->data_source as $k => $rec) {
            if (key_exists($k,$this->data_dest)) {
                if ($rec != $this->data_dest[$k]) {
                    foreach($rec as $i => $v) { 
                        $rec[$i] = utf8_encode($v); 
                    }
                    if ($rec != $this->data_dest[$k]) {
                        array_push($rec,$k);
                        $this->data_update[] = $rec;
                    }
                }
            } else {
                $this->data_insert[] = $rec;
           }
        }
    
    }
    
    public function insert($sql)
    {
        if (!empty($this->data_insert)) {
            $this->__dbd->exec_multi($sql,$this->data_insert);
            $this->message .= "Insert executed ".count($this->data_insert)."\n";
        }
    }
    
    public function update($sql)
    {
        if (!empty($this->data_update)) {
            $this->__dbd->exec_multi($sql,$this->data_update);
            $this->message .= "Update executed ".count($this->data_update)."\n";
        }
    }
    
    public function end()
    {
        return $this->message .= "Tempo impiegato : ".(microtime(true) - $this->time_start);
    }
}
