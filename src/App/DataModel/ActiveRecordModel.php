<?php
namespace Opensymap\App\DataModel;

use Opensymap\App\DataModel\FieldModel;

class ActiveRecordModel implements InterfaceModel
{
    private $properties;
    private $fields = array();
    private $identityFields = array();
    private $db;
    
    public function __construct($db, $properties, $fields)
    {
        $this->db = $db;
        $this->properties = $properties;
        var_dump($properties);
        foreach ($fields as $fieldId => $field) {
            $dbField = $field['name'];
            $this->fields[$dbField] = new FieldModel($field);        
            if ($this->fields[$dbField]->isPrimaryKey()) {
                $this->identityFields[] =&  $this->fields[$dbField];
            }
        }
    }
   
    public function save()
    {
        $params = array();
        $table  = $this->properties['table'];
        $where  = array();
        foreach ($this->fields as $dbField => $field) {
            $params[$field->name] = $field->getValue();
            if (!empty($_REQUEST['pkey']) && $field->isPrimaryKey() && !empty($_REQUEST['pkey'][$field->name])) {
                $where[$field->name] = $_REQUEST[$field->name];
            }
        }        
        if (empty($where)) {
            $this->insert($table, $params);
            return;
        }
        $this->update($table,$params, $where);
    }
    
    public function load($identityValues)
    {
        $par = array();
        foreach ($this->identityFields as $k => $field) {
            if (!empty($identityValues) && !empty($identityValues[$field->name])) {
                $par[$field->name] = $identityValues[$field->name];
            }
        }
        if (empty($par) || count($par) != count($this->identityFields)) {
            return;
        }
        $sql  = "SELECT * ";
        $sql .= "FROM {$this->properties['databaseTable']} ";
        $sql .= "WHERE ".implode(' = ?, ',array_keys($par)).' = ?';
        $rec = $this->db->exec_unique(
            $sql, 
            array_values($par), 
            'ASSOC'
        );
        foreach ($rec as $fieldName => $fieldValue) {
            if (!array_key_exists($fieldName, $this->fields)) {
                continue;
            }
            $_REQUEST[$this->fields[$fieldName]->getHtmlName()] = $fieldValue;
        }       
    }
    
    public function delete()
    {
        $table = $this->properties['table'];
        $strSQL = 'DELETE FROM '.$table;
        $strSQL .= 'WHERE (\''.implode('\', \'',array_values($where)).'\')';
        var_dump($strSQL);
    }
    
    public function insert($table, $params)
    {
        $strSQL = 'INSERT INTO '.$table;
        $strSQL .= ' ('.implode(', ',array_keys($params)).')';
        $strSQL .= ' VALUES ';
        $strSQL .= ' (\''.implode('\', \'',array_values($params)).'\')';
        var_dump($strSQL);
    }
    
    public function update($table, $params, $where)
    {
        $strSQL = 'UPDATE '.$table.' SET ';
        $strSQL .= implode(' = ?, ',array_keys($params)).' = ?';
        $field = array();
        foreach ($params as $k => $par) {
            $field[] = "$k = ?";
        }
        //$strSQL .= implode(', ',$field);
        var_dump($strSQL);
    }
}
