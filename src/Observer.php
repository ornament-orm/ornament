<?php

namespace Ornament;

use SplSubject;

trait Observer
{
    public abstract function update(SplSubject $subject);
}

