<?php

namespace monolyth;
use SplObserver;

trait Subject_Model
{
    private $_observers = [];

    /**
     * Attach an observer model.
     *
     * @param SplObserver $observer The observer model (does not necessarily
     *                              have to be a Monolyth model).
     */
    public function attach(SplObserver $observer)
    {
        $this->_observers[spl_object_hash($observer)] = $observer;
    }
    
    /**
     * Detach an observer model.
     *
     * @param SplObserver $observer The observer model (does not necessarily
     *                              have to be a Monolyth model).
     */
    public function detach(SplObserver $observer)
    {
        unset($this->_observers[spl_object_hash($observer)]);
    }
    
    /**
     * Notify all registered observers.
     */
    public function notify()
    {
        foreach ($this->_observers as $observer) {
            $observer->update($this);
        }
    }
}

