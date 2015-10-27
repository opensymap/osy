<?php
namespace Opensymap\App\DataModel;

interface InterfaceModel
{
    public function __construct($db, $properties, $fields);
    
    public function save();
    
    public function delete();
}