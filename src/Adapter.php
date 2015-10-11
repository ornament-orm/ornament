<?php

namespace Ornament;

interface Adapter
{
    public function query($model, array $ps, array $opts = []);
    public function load(Container $model);
    public function create(Container $model);
    public function update(Container $model);
    public function delete(Container $model);
}

