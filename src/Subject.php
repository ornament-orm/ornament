<?php

namespace Ornament;

use SplObserver;

trait Subject
{
    private $__observers = [];

    /**
     * Attach an observer model.
     *
     * @param SplObserver $observer The observer model to detach.
     */
    public function attach(SplObserver $observer)
    {
        $this->__observers[spl_object_hash($observer)] = $observer;
    }
    
    /**
     * Detach an observer model.
     *
     * @param SplObserver $observer The observer model to detach.
     */
    public function detach(SplObserver $observer)
    {
        unset($this->__observers[spl_object_hash($observer)]);
    }
    
    /**
     * Notify all registered observers.
     */
    public function notify()
    {
        foreach ($this->__observers as $observer) {
            $observer->update($this);
        }
    }
}

