<?php
namespace Opensymap\App\DataModel;

use Opensymap\App\DataModel\FieldModel;

class EntityAttributeValueModel implements InterfaceModel
{
    public function __construct($db, $properties, $identityValues, $response = null, $dispatcher = null)
    {
        $this->db = $db;
        $this->properties = $properties;
        $this->response = $response;
        $this->dispatcher = $dispatcher;
        $this->identityValues = $identityValues;
    }
    
    private function getIdentityCondition()
    {
        $conditions = $values = array();
        foreach ($this->fields as $dbField => $field) {
            $values[$field->name] = $field->getValue();
            if (
                !empty($this->identityValues) && 
                $field->isPrimaryKey() && 
                !empty($this->identityValues[$field->name])
            ) {
                $conditions[$field->name] = $this->identityValues[$field->name];
            }
        }
        
        if (empty($conditions) || count($conditions) != count($this->identityFields)) {
            $conditions = null;
        }
        return array($conditions, $values);
    }
    
    public function map($dbField, $fieldProp) 
    {
        $this->fields[$dbField] = new FieldModel($fieldProp);
        if ($this->fields[$dbField]->isPrimaryKey()) {
            $this->identityFields[] =&  $this->fields[$dbField];
        }
        var_dump($this->properties);
    }
    
    public function delete()
    {
        
    }
    
    public function load()
    {
        if (!is_array($this->identityValues)) {
            return;
        }
        $sql = "SELECT * ";
        $sql .= "FROM {$this->properties['databaseTable']} ";
        $sql .= "WHERE {$this->properties['fieldForeignKey']} = ?";
        $par = array_values($this->identityValues);
        $rs = $this->db->exec_query($sql,$par,'ASSOC');
        foreach($rs as $rec) {
            
            foreach($this->fields as $field) {
                
                if ($rec[$this->properties['fieldProperty']] == $field->propertyConstant) {
                    var_dump($rec[$this->properties['fieldValue']] , $field->fieldViewAssoc);
                    $_REQUEST[$field->fieldViewAssoc] = $rec[$this->properties['fieldValue']];
                }
            }
        }
        //var_dump($sql,$par);
    }
    
    public function save()
    {
        
    }
}
