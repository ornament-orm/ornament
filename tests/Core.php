<?php

namespace Ornament\Tests;

use Ornament\Demo\CoreModel;
use Ornament\Demo\DecoratedModel;
use Error;

/**
 * Tests for core Ornament functionality.
 */
class Core
{
    /**
     * Core models should have only the basic functionality: expose properties
     * via magic getters and setters {?} {?} but not private ones {?}.
     */
    public function testMakeModel(CoreModel $model)
    {
        yield assert(isset($model->id));
        yield assert($model->id == 1);
        yield assert(!isset($model->invisible));
    }

    /**
     * Core models should correctly report their state as new {?} or dirty {?}.
     */
    public function testModelState(CoreModel $model)
    {
        yield assert($model->isNew());
        $model->name = 'Linus';
        yield assert($model->isDirty());
    }

    /**
     * Models can successfully register and apply decorations {?}.
     */
    public function testDecorators(DecoratedModel $model)
    {
        $model->field = 2;
        yield assert((int)"{$model->field}" === 1);
    }

    /**
     * If we try to access a private property, an Error is thrown {?}.
     */
    public function testPrivate(CoreModel $model)
    {
        $e = null;
        try {
            $foo = $model->invisible;
        } catch (Error $e) {
        }
        yield assert($e instanceof Error);
    }

    /**
     * If we try to modify a protected property, an Error is thrown {?}.
     */
    public function testProtected(CoreModel $model)
    {
        $e = null;
        try {
            $model->id = 2;
        } catch (Error $e) {
        }
        yield assert($e instanceof Error);
    }
}

