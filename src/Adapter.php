<?php

namespace Ornament\Ornament;

interface Adapter
{
    public function setPrimaryKey($field);
    public function setIdentifier($identifier);
    public function setFields(array $fields);
    public function setAnnotations(array $annotations);
    public function setAdditionalQueryParameters(array $parameters);
    public function query($model, array $ps, array $opts = [], array $ctor = []);
    public function load(Container $model);
    public function create(Container $model);
    public function update(Container $model);
    public function delete(Container $model);
}

