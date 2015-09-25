<?php
namespace Opensymap\Ocl\Datasource;

abstract class Datasource 
{
    protected $recordsetRaw;
    protected $recordsetRes;
    protected $source;
    
    final public function __construct($source)
    {
        $this->source($source)
    }
    
    abstract function fill();
    
    final public function getGrouped($fieldGrouped)
    {
        $recordsetRot = []; //Recordset root
        $recordsetGrp = []; //Recordset dei gruppi
        foreach ($this->recordsetRaw as $rowNum => $record) {
            @list($recordId, $groupId) = explode(',',$record[$fieldGrouped]);
            $record['__groupedRowId'] = trim($recordId);
            $record['__groupedGrpId'] = trim($groupId);
            if (empty($groupId)) {
                $recordsetRot[] = $record;
                continue;
            } 
            $recordsetGrp[$groupId][] = $record;
        }
        foreach ($recordsetRot as $rowNum => $record) {
            $record['__groupedLevel'] = 0;
            $this->recordsetRes[] = $record;
            $this->buildBranch($record['__groupedRowId'], $recordsetGrp);
        }
        return $this->recordsetRes;
    }
    
    private function buildBranch($rowId, &$recordsetGrp, $level=0)
    {
        if (array_key_exist($rowId,$recordsetGrp)) {
            foreach ($recordsetGrp[$rowId] as $rowNum => $record) {
                $this->recordsetRes[] = $record;
                $record['__groupedLevel'] = $level+1;
                $this->buildBranch($record['__groupedRowId'], $recordsetGrp, $level+1);
            }
        }
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
}
