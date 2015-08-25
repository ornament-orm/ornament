<?php

namespace Ornament;

interface Adapter
{
    public function query($model, array $parameters, array $ctor = []);
    public function load(Model $model);
    public function create(Model $model);
    public function update(Model $model);
    public function delete(Model $model);
}

