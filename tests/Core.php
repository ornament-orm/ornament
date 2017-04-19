<?php

namespace Ornament\Ornament\Tests;

use Ornament\Ornament\Demo\CoreModel;
use Ornament\Ornament\Demo\DecoratedModel;

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
     * Models can successfully register and apply decorations {?}. We can apply
     * an arbitrary number of decorators which get applied in order {?}.
     */
    public function testDecorators(DecoratedModel $model)
    {
        $model->field = 2;
        yield assert($model->field == 1);
        $model->two_decorators = 1;
        yield assert($model->two_decorators == 4);
    }
}

