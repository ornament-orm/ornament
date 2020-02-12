<?php

namespace Ornament\Core;

use stdClass;

abstract class Decorator implements DecoratorInterface
{
    protected $source;

    /**
     * Constructor. Pass in the parent model, property name and optional extra
     * arguments (not used by default, but implementing classes might).
     *
     * @param mixed $source;
     * @param mixed ...$args
     * @return void
     */
    public function __construct($source, ...$args)
    {
        $this->source = $source;
    }

    /**
     * Get the original source, i.e. $model->$property.
     *
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * __toString the source.
     *
     * @return string
     */
    public function __toString() : string
    {
        return (string)$this->getSource();
    }
}

