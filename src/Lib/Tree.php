<?php
namespace Opensymap\Lib;

class Tree
{
    private $db = null;
    private $sql = null;
    private $dataRaw = array();
    private $dataRes = array();
    private $gidApp = array();

    public function __construct($sql, $db, $gidEna)
    {
        $this->db = $db;
        $this->sql = $sql;
        $this->gidApp = is_array($gidEna) ? $gidEna : explode(',', $gidEna);
        $this->build();
    }

    private function build()
    {
        $res = $this->db->exec_query($this->sql, null, 'NUM');
        
        foreach ($res as $k => $rec) {
            //Pos 0 => id
            //Pos 1 => parent
            //Pos 2 => id che hanno accesso al livello;
            $rec[1] = empty($rec[1]) ? '0' : $rec[1];
            $this->dataRaw[$rec[1]][] = $rec;
        }
        $this->buildTreeCond();
    }

    private function buildTreeCond($fid = '0', $p_acc = 'all', $p_dis = null)
    {
        //echo $fid.'<br>';
        if (!array_key_exists($fid, $this->dataRaw)) {
            return;
        }
        
        foreach ($this->dataRaw[$fid] as $k => $rec) {
            $ena = true;
            if ($rec[2] == 'parent') {
                $rec[2] = $p_acc;
            }
            if ($rec[3] == 'parent') {
                $rec[3] = $p_dis;
            }
            if ($rec[2] != 'all') {
                $a = explode(',', $rec[2]);
                $d = array_intersect($a, $this->gidApp);
                if (empty($d)) {
                    $ena = false;
                }
            }
            if (!empty($ena) && !empty($rec[3])) {
                $a = explode(',', $rec[3]);
                $d = array_diff($a, $this->gidApp);
                if (empty($d)) {
                    $ena = false;
                }
            }
            if ($ena) {
                $this->dataRes[] = $rec[0];
                $this->buildTreeCond($rec[0], $rec[2], $rec[3]);
            }
        }
        
    }

    public function get()
    {
        return $this->dataRes;
    }
}
