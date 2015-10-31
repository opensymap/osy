<?php
namespace Opensymap\App\DataModel;

use Opensymap\App\DataModel\FieldModel;

class EntityAttributeValueModel implements InterfaceModel
{
    private $properties = array();
    private $fields = array();
    
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
        $this->dispatcher->dispatch('delete-before');
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
        $this->dispatcher->dispatch('delete-after');
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
                if (array_key_exists($field->propertyConstant,$propertyCollection)) {
                    $_REQUEST[$field->fieldViewAssoc] = $propertyCollection[$field->propertyConstant];
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
        $sql .= "WHERE {$this->properties['fieldForeignKey']} = ?";
        $par = array_values($identityValues);
        $rs = $this->db->exec_query($sql, $par, 'ASSOC');

        foreach ($rs as $rec) {
            $property = $rec[$this->fieldProperty];
            $propertyCollection[$property] = $rec[$this->fieldValue];
        }
        
        return $propertyCollection;
    }
    
    public function save()
    {
        $propertyCollection = $this->loadEavCollection();
        foreach ($this->fields as $field) {
            $values = array(
                $this->fieldValue => $_REQUEST[$field->fieldViewAssoc]
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
    }
    
    private function insert($values)
    {
        $this->dispatcher->dispatch('insert-before');
        $this->db->insert($this->databaseTable, $values);
        $this->dispatcher->dispatch('insert-after');
    }
    
    private function update($values, $condition)
    {
        $this->dispatcher->dispatch('update-before');
        $this->db->update($this->databaseTable, $values, $condition);
        $this->dispatcher->dispatch('update-after');
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
