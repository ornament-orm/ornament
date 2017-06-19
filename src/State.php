<?php

namespace Ornament\Core;

use StdClass;

trait State
{
    /**
     * Returns true if any of the model's properties was modified.
     *
     * @return bool
     */
    public function isDirty() : bool
    {
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
     */
    public function isPristine() : bool
    {
        return !$this->isDirty();
    }

    /**
     * Returns true if a specific property on the model is dirty.
     *
     * @return bool
     */
    public function isModified($property)
    {
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
     */
    public function markPristine()
    {
        $this->__init = clone $this->__state;
    }
}

