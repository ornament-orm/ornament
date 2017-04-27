<?php

namespace Ornament\Demo;

use Ornament\Core\Model;
use Ornament\Core\State;

class CoreModel
{
    use Model;
    use State;

    protected $id = 1;
    public $name = 'Marijn';
    private $invisible = true;
}

