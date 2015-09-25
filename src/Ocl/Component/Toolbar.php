<?php 
namespace Opensymap\Ocl\Component;

use Opensymap\Ocl\Component\AbstractComponent;

class toolbar extends AbstractComponent
{
    public function __construct($nam)
    {
        parent::__construct('div',$nam);
        $this->att('class','toolbar');
    }

    protected function build()
    {
    }
}
