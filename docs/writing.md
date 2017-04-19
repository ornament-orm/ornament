# Writing your own decorators
Each decorator consists of an annotated interface or trait defining the class
to use as a decorator, as well as the decorator class itself. Decorator classes
must be instances of `Ornament\Ornament\Decorator`.
Each decorator lives inside a trait (although traits can define multiple
decorators). The decorating is done inside trait methods and these are annotated
to define what they should work on, and how.

Let's make a sample decorator to see how this works:

```php
<?php

trait SampleDecorator
{
    /**
     * @Before save
     */
    protected function beforeSaveExample()
    {
        // This method is called _before_ every call to `save` on a model.
        // If other decorators also define a 'before save' decorator, they're
        // called in order.
        // E.g., this could do some validation on the model's state.
    }

    /**
     * @After delete
     */
    protected function afterDeleteExample()
    {
        // This method is called _after_ every call to `delete` on a model.
        // If other decorators also define an 'after delete' decorator, they're
        // called in order.
        // E.g., you could delete some regular files here associated with the
        // model on disc.
    }

    /**
     * @Decorator Foo
     */
    protected function exampleFooDecorator($value)
    {
        return strrev($value);
    }
}

// And in the model;
class MyModel
{
    use Ornament\Ornament\Model;
    use SampleDecorator;

    /** @Foo */
    protected $bar;
}

$test = new MyModel;
$test->bar = 'Marijn';

