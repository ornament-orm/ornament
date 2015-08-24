<?php

namespace Ornament;

interface Adapter
{
    public function create(Model $model);
    public function update(Model $model);
    public function delete(Model $model);
}

