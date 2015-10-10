<?php

/**
 * A trait to use in conjunction with the SplSubject interface to quickly
 * implement a model/observer pattern.
 */
namespace Ornament;

use SplObserver;
use SplObjectStorage;

trait Subject
{
    private $__observers;

    /**
     * Attach an observer model.
     *
     * @param SplObserver $observer The observer model to attach.
     */
    public function attach(SplObserver $observer)
    {
        if (!isset($this->__observers)) {
            $this->__observers = new SplObjectStorage;
        }
        $this->__observers->attach($observer);
    }
    
    /**
     * Detach an observer model.
     *
     * @param SplObserver $observer The observer model to detach.
     */
    public function detach(SplObserver $observer)
    {
        if (!isset($this->__observers)) {
            $this->__observers = new SplObjectStorage;
        }
        $this->__observers->detach($observer);
    }
    
    /**
     * Notify all registered observers.
     */
    public function notify()
    {
        if (!isset($this->__observers)) {
            $this->__observers = new SplObjectStorage;
        }
        foreach ($this->__observers as $observer) {
            $observer->update($this);
        }
    }
}

