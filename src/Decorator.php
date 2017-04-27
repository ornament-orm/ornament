<?php

namespace Ornament\Core;

interface Decorator
{
    public function getSource();
    public function __toString() : string;
}

