<?php

namespace Ornament\Core;

interface DecoratorInterface
{
    public function getSource();

    public function __toString() : string;
}

