<?php
namespace Opensymap\Ocl\Datasource;

class DatasourceDbo extends Datasource
{
    private $query;
    private $fetchMethod;
    
    public function setQuery($query, $fetchMethod='ASSOC')
    {
        $this->query = $query;
        $this->fetchMethod = $fetchMethod;
    }
    
    public function fill()
    {
        $this->recordsetRaw = $this->source->exec_query($this->query)
    }
}
