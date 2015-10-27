<?php
namespace Opensymap\App;

interface InterfaceModel
{
    public function __construct($dbo, $definition);
    
    public function save();
    
    public function delete();
    
    public function setDefinition($definition);
}
