<?php
namespace Opensymap\App\DataModel;

use Opensymap\App\DataModel\FieldModel;

class EntityAttributeValueModel implements InterfaceModel
{
    
    private $fields = array();
    private $identityValues = array();
    private $properties = array();
    
    public function __construct($db, $properties, $identityValues, $response = null, $dispatcher = null)
    {
        $this->properties = $properties;
        $this->db = $db;    
        $this->response = $response;
        $this->dispatcher = $dispatcher;
        $this->identityValues = $identityValues;
    }
    
    public function map($dbField, $fieldProp) 
    {
        $this->fields[$dbField] = new FieldModel($fieldProp);
        if ($this->fields[$dbField]->isPrimaryKey()) {
            $this->identityFields[] =&  $this->fields[$dbField];
        }
    }
    
    public function delete()
    {
        $this->dispatcher->dispatch('delete-before', null, $this->id);
        if ($this->deleteOnlyLinkedProperty){
            foreach ($this->fields as $field) {
                //Delete only direct property
                if (array_key_exists($field->propertyConstant, $propertyCollection)) {
                    $condition = array(
                        $this->fieldProperty => $field->propertyConstant,
                        $this->fieldForeignKey => array_values($this->identityValues)[0]
                    );
                    $this->db->update($this->databaseTable, $condition);
                }
            }
        } else {
            $condition = array(
                $this->fieldForeignKey => array_values($this->identityValues)[0]
            );
            $this->db->delete($this->databaseTable, $condition);
        }
        $this->dispatcher->dispatch('delete-after', null, $this->id);
    }

    public function isSoftDelete()
    {
        return !empty($this->properties['softDelete']);
    }
    
    public function load()
    {
        $propertyCollection = $this->loadEavCollection();
        if (!empty($propertyCollection)) {
            foreach ($this->fields as $field) {
                if (
                    array_key_exists(
                        $field->propertyConstant, 
                        $propertyCollection
                    ) && 
                    !array_key_exists(
                        $field->fieldViewAssoc,
                        $_REQUEST)
                    ) {
                    $_REQUEST[$field->fieldViewAssoc] = $propertyCollection[$field->propertyConstant][$field->name];
                }
            }
        }
    }
    
    private function loadEavCollection()
    {
        $propertyCollection = array();
        $identityValues = $this->identityValues;
        if (!is_array($identityValues)) {
            return $propertyCollection;
        }
        
        $sql = "SELECT * ";
        $sql .= "FROM {$this->properties['databaseTable']} ";
        $sql .= "WHERE {$this->properties['fieldForeignKey']} = ? ";
        if ($this->identityCondition) {
            $sql .= " AND ( {$this->identityCondition} )";
        }
        
        $par = array_values($identityValues);
        $rs = $this->db->exec_query($sql, $par, 'ASSOC');

        foreach ($rs as $rec) {
            $property = $rec[$this->fieldProperty];
            //$propertyCollection[$property] = $rec[$field->fieldValue];
            
            foreach ($this->fields as $field) {
                if ($property != $field->propertyConstant) {
                    continue;
                }
                $propertyCollection[$property][$field->name] = $rec[$field->name];
            }
        }
        
        return $propertyCollection;
    }
    
    public function save()
    {
        if (empty($this->identityValues)){
            return;
        }
        $propertyCollection = $this->loadEavCollection();
        
        foreach ($this->fields as $field) {
            //TODO : MULTIDIMENSIONAL UPDATE/INSERT
            $values = array(
                $field->name => $_REQUEST[$field->fieldViewAssoc]
            );
            
            if (!array_key_exists($field->propertyConstant, $propertyCollection)) {
                $values[$this->fieldProperty] = $field->propertyConstant;
                $values[$this->fieldForeignKey] = array_values($this->identityValues)[0];
                $this->insert($values);
            } else {
                $condition = array(
                    $this->fieldProperty => $field->propertyConstant,
                    $this->fieldForeignKey => array_values($this->identityValues)[0]
                );
                $this->update($values, $condition);
            }
        }
        return $this->response;
    }
    
    private function insert($values)
    {
        $this->dispatcher->dispatch('insert-before', null, $this->id);
        $this->db->insert($this->databaseTable, $values);
        $this->dispatcher->dispatch('insert-after', null, $this->id);
    }
    
    private function update($values, $condition)
    {
        $this->dispatcher->dispatch('update-before', null, $this->id);
        $this->db->update($this->databaseTable, $values, $condition);
        $this->dispatcher->dispatch('update-after', null, $this->id);
    }

    public function __get($key)
    {
        return array_key_exists($key,$this->properties) ? $this->properties[$key] : null;
    }
    
    public function __set($key, $val)
    {
        $this->properties[$key] = $val;
    }
}
