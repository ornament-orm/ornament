<?php

namespace monolyth;
use SplSubject;

trait Observer_Model
{
    public abstract function update(SplSubject $subject);
}

