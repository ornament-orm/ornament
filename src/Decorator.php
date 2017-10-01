<?php

namespace Ornament\Core;

use StdClass;

abstract class Decorator implements DecoratorInterface
{
    protected $source;

    /**
     * Constructor. Pass in the parent model, property name and optional extra
     * arguments (not used by default, but implementing classes might).
     *
     * @param stdClass $model
     * @param string $property
     * @param mixed ...$args
     * @return void
     */
    public function __construct(StdClass $model, string $property, ...$args)
    {
        $this->source =& $model->$property;
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

