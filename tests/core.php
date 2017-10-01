<?php

use Ornament\Demo\CoreModel;
use Ornament\Demo\DecoratedModel;
use Gentry\Gentry\Wrapper;

/**
 * Tests for core Ornament functionality.
 */
return function () : Generator {
    /**
     * Core models should have only the basic functionality: expose properties
     * via magic getters and setters but not private ones.
     */
    yield function () {
        $model = Wrapper::createObject(CoreModel::class);
        assert(isset($model->id));
        assert($model->id == 1);
        assert(!isset($model->invisible));
    };

    /**
     * Models can successfully register and apply decorations. The second
     * field is decorated using constructor arguments.
     */
    yield function () {
        $model = Wrapper::createObject(DecoratedModel::class);
        $model->field = 2;
        assert((int)"{$model->field}" === 1);
        $model->anotherField = 1;
        assert((int)"{$model->anotherField}" === 6);
    };

    /**
     * If we try to access a private property, an Error is thrown.
     */
    yield function () {
        $model = Wrapper::createObject(CoreModel::class);
        $e = null;
        try {
            $foo = $model->invisible;
        } catch (Error $e) {
        }
        assert($e instanceof Error);
    };

    /**
     * If we try to modify a protected property, an Error is thrown.
     */
    yield function () {
        $model = Wrapper::createObject(CoreModel::class);
        $e = null;
        try {
            $model->id = 2;
        } catch (Error $e) {
        }
        assert($e instanceof Error);
    };
};

