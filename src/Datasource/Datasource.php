<?php
namespace Opensymap\Datasource;

abstract class Datasource 
{
    protected $recordsetRaw;
    protected $recordsetRes;
    protected $source;
    protected $trasformRow;
    protected $trasformCol;
    protected $columns;
    
    final public function __construct($source)
    {
        $this->source = $source;
    }
    
    abstract function fill();
    
    //Return recordset
    final public function get()
    {
        
        if ($this->trasformRow) {
            $this->recordsetRaw = array_map(
                $this->trasformRow,
                $this->recordsetRaw
            );
        }
        return $this->recordsetRaw;
    }
    
    public function getColumns()
    {
        if ($this->trasformCol) {
            $fnc = $this->trasformCol;
            return $fnc($this->columns);
        }
        return $this->columns;
    }
    
    final public function getGrouped($fieldGrouped)
    {
        $recordsetRoot = []; //Recordset root
        $recordsetGroup = []; //Recordset dei gruppi
        foreach ($this->recordsetRaw as $rowNum => $record) {
            @list($recordId, $groupId) = explode(',',$record[$fieldGrouped]);
            $record['__groupedRowId'] = trim($recordId);
            $record['__groupedGrpId'] = trim($groupId);
            if (empty($groupId)) {
                $recordsetRoot[] = $record;
                continue;
            } 
            $recordsetGroup[$groupId][] = $record;
        }
        //var_dump($recordsetGroup);
        foreach ($recordsetRoot as $rowNum => $record) {
            $i = count($this->recordsetRes);
            $record['__groupedLevel'] = 0;
            $this->recordsetRes[] = $record;
            $this->recordsetRes[$i]['__groupedType'] = $this->buildBranch($record['__groupedRowId'], $recordsetGroup);
            $this->recordsetRes[$i]['__groupedPos'] = $rowNum;
        }
        if (!empty($i)) {
            $this->recordsetRes[$i]['__groupedPos'] = 'last';
        }
        
        return $this->recordsetRes;
    }
    
    private function buildBranch($rowId, &$recordsetGroup, $level=0)
    {
        if (array_key_exists($rowId, $recordsetGroup)) {
            foreach ($recordsetGroup[$rowId] as $rowNum => $record) {
                $i = count($this->recordsetRes);
                $record['__groupedPos'] = '';
                $record['__groupedLevel'] = $level+1;
                $this->recordsetRes[] = $record;
                $this->recordsetRes[$i]['__groupedType'] = $this->buildBranch($record['__groupedRowId'], $recordsetGroup, $level+1);
                $this->recordsetRes[$i]['__groupedPos'] = $rowNum;
            }
            if (!empty($i)) {
                 $this->recordsetRes[$i]['__groupedPos'] = 'last';
            }
            return 'branch';
        }
        return 'leaf';
    }
    
    final public function getPivot($pivotField = '_pivot')
    {
       $data = array();
       $hcol = array();
       $hrow = array();
       $fcol = null;
       foreach ($this->recordsetRaw as $rowNum => $record) {
            $column = $row = null;
            foreach ($record as $field => $value) {
                if ($field == $pivotField) {
                    $column = $value;
                    if (!in_array($column, $hcol)){
                       $hcol[] = $column;
                    }
                    continue;
                }
                if (is_null($column)) {
                    if (empty($rowNum)) {
                       $hcol[0] = $field;
                    }
                    $row = $value;
                    if (!in_array($row, $hrow)) {
                       $hrow[] = $row;
                    }
                    continue;
                }
                $data[$column][$row][] = $value;
            }
       }

        $dataPivot = array();
        ksort($hrow); 
        ksort($hcol);
        foreach ($hrow as $row) {
            foreach ($hcol as $i => $col) {
                if (empty($i)){
                   $drow[$col] = $row; //Aggiuno la label della riga
                   continue;
                } 
                $drow[$col] = array_key_exists($row,$data[$col]) ? array_sum($data[$col][$row]) : '0';
            }
            $dataPivot[] = $drow;
        }
        $this->recordRes = $dataPivot;
        return $hcol;
    }
    
    final public function trasformCol($fnc)
    {
        $this->trasformCol = $fnc;
    }
    
    final public function trasformRow($fnc)
    {
        $this->trasformRow = $fnc;
    }
}
