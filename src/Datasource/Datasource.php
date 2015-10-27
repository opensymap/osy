<?php
namespace Opensymap\Datasource;

abstract class Datasource implements \IteratorAggregate, InterfaceDatasourcePaging
{
    protected $recordsetRaw = array();
    protected $recordsetRes = array();
    protected $source;
    protected $trasformRow;
    protected $trasformCol;
    protected $columns;
    protected $build = false;
    
    final public function __construct($source)
    {
        $this->source = $source;
    }
    
    abstract protected function fill();
    
    //Return recordset
    final public function get()
    {
        if ($this->build) {
            return $this->recordsetRaw;
        }
        $this->fill();
        if (!empty($this->columns)) {
            foreach ($this->columns as $column) {
                switch ($column['name']) {
                    case '_tree':
                        $this->recordsetRaw = $this->getGrouped('_tree');
                        break 2;
                    case '_pivot':
                        list($this->columns, $this->recordsetRaw) = $this->getPivot('_pivot');
                        break 2;
                }
            }
        }
        $this->build = true;
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
        if (empty($this->trasformCol)) {
            return $this->columns;
        }
        $fnc = $this->trasformCol;
        return $fnc($this->columns);
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
        //var_dump($this->recordsetRaw);
        foreach ($this->recordsetRaw as $rowNum => $record) {
            $column = $row = null;
            foreach ($record as $field => $value) {
                if ($field == $pivotField) {
                    $column = '$'.$value;
                    if (!in_array($column, $hcol)) {
                       $hcol[] = $column;
                    }
                    continue;
                } elseif ($field[0] == '_') {
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
                if (!empty($column) && !empty($row)) {
                    $data[$column][$row][] = $value;
                }
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
        foreach($hcol as $i => $column){
            $hcol[$i] = array('name'=>$column,'native_type'=>1);
        }
        $this->recordRes = $dataPivot;
        return array($hcol, $dataPivot);
    }
    
    final public function trasformCol($fnc)
    {
        $this->trasformCol = $fnc;
        return $this;
    }
    
    final public function trasformRow($fnc)
    {
        $this->trasformRow = $fnc;
        return $this;
    }
    
    public function __toString()
    {
        return 'datasource';
    }
    
    public function getIterator()
    {
        return new \ArrayIterator($this->get());
    }
}
