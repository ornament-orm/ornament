<?php

namespace Ornament;

interface Adapter
{
    public function query($model, array $ps, array $opts = []);
    public function load(Model $model);
    public function create(Model $model);
    public function update(Model $model);
    public function delete(Model $model);
}

