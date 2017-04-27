<?php

namespace Ornament\Core;

abstract class Decorator implements DecoratorInterface
{
    protected $source;

    public function __construct($source, ...$args)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function __toString() : string
    {
        return (string)$this->getSource();
    }
}

