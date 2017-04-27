<?php

namespace Ornament\Demo;

use Ornament\Core\Model;

class CoreModel
{
    use Model;

    protected $id = 1;
    public $name = 'Marijn';
    private $invisible = true;
}

