<?php

namespace Ornament\Core;

use StdClass;
use DomainException;

trait State
{
    /**
     * Returns true if any of the model's properties was modified.
     *
     * @return bool
     * @throws DomainException if the current object is not an Ornament model.
     */
    public function isDirty() : bool
    {
        if (!isset($this->__initial)) {
            throw new DomainException(sprintf(
                "%s is not an Ornament model.",
                get_class($this)
            ));
        }
        foreach ($this->__state as $prop => $val) {
            if ($this->isModified($prop)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if the model is still in pristine state.
     *
     * @return bool
     * @throws DomainException if the current object is not an Ornament model.
     */
    public function isPristine() : bool
    {
        if (!isset($this->__initial)) {
            throw new DomainException(sprintf(
                "%s is not an Ornament model.",
                get_class($this)
            ));
        }
        return !$this->isDirty();
    }

    /**
     * Returns true if a specific property on the model is dirty.
     *
     * @return bool
     * @throws DomainException if the current object is not an Ornament model.
     */
    public function isModified($property)
    {
        if (!isset($this->__initial)) {
            throw new DomainException(sprintf(
                "%s is not an Ornament model.",
                get_class($this)
            ));
        }
        if (!isset($this->__state->$property) && isset($this->__initial->$property)) {
            return true;
        }
        if (isset($this->__state->$property) && !isset($this->__initial->$property)) {
            return true;
        }
        if ($this->__state->$property != $this->__initial->$property) {
            return true;
        }
        return false;
    }

    /**
     * Mark the current model as 'pristine', i.e. not dirty.
     *
     * @return void
     * @throws DomainException if the current object is not an Ornament model.
     */
    public function markPristine()
    {
        if (!isset($this->__initial)) {
            throw new DomainException(sprintf(
                "%s is not an Ornament model.",
                get_class($this)
            ));
        }
        $this->__init = clone $this->__state;
    }
}

