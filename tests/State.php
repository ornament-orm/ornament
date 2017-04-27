<?php

namespace Ornament\Tests;

use Ornament\Demo\StateModel;

/**
 * Tests for stateful models.
 */
class State
{
    /**
     * Stateful models should correctly report their state as pristine {?} or
     * dirty {?}.
     */
    public function testModelState(StateModel $model)
    {
        yield assert($model->isPristine());
        $model->name = 'Linus';
        yield assert($model->isDirty());
    }
}

