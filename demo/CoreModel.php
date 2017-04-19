<?php

namespace Ornament\Ornament\Demo;

use Ornament\Ornament\Model;

class CoreModel
{
    use Model;

    protected $id = 1;
    public $name = 'Marijn';
    private $invisible = true;
}

