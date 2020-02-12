# Ornament
PHP7 ORM toolkit, core package

ORM is a fickle beast. Many libraries (e.g. Propel, Doctrine, Eloquent etc)
assume your database should correspond to your models. This is simply not the
case; models contain business logic and may, may not or may in part refer to
database tables, NoSQL databases, flat files, an external API or whatever (the
"R" in ORM should really stand for "resource", not "relational"). The point is:
the models shouldn't care, and there should be no "conventional" mapping through
their names. (A common example would be a model of pages in multiple languages,
where the data might be stored in a `page` table and a `page_i18n` table for the
language-specific data.)

Also, the use of extensive and/or complicated config files sucks. (XML? This
is 2017, people!)

Ornament's design goals are:

- make it super-simple to use vanilla PHP classes as models;
- promote the use of models as "dumb" data containers;
- encourage offloading of storage logic to helpers classes (repositories);
- make models extensible via an easy plugin mechanism.

## Installation
```sh
$ composer require ornament/core
```

You'll likely also want auxiliary packages from the `ornament/*` family.

## Basic usage
Ornament models (or "entities" if you're used to Doctrine-speak) are really
nothing more than vanilla PHP classes; there is no need to extend any base
object of sorts (since you might want to do that in your own framework).

Ornament is a _toolkit_, so it supplies a number of `Trait`s one can `use` to
extend your models' behaviour beyond the ordinary.

The most basic implementation would look as follows:

```php
<?php

use Ornament\Core\Model;

class MyModel
{
    // The generic Model trait that bootstraps this class as an Ornament model;
    // it contains core functionality.
    use Model;

    // All protected properties on a model are considered read-only.
    protected $id;

    // Public properties are read/write. To auto-decorate during setting, use
    // the `Model::set()` method.
    public $name;

    // Private properties are just that: private. They're left alone:
    private $password;
}

// Assuming $source is a handle to a data source (in this case, a PDO
// statement):
$model = MyModel::fromIterable($source->fetch(PDO::FETCH_ASSOC));
echo $model->id; // 1
echo $model->name; // Marijn
echo $model->password; // Error: private property.
$model->name = 'Linus'; // Ok; public property.
$model->id = 2; // Error: read-only property.
```

The above example didn't do much yet except exposing the protected `id` property
as read-only. Note however that Ornament models also prevent mutating undefined
properties; trying to set anything not explicitly set in the class definition
will throw an `Error` mimicking PHP's internal error when accessing protected or
private properties.

## Annotating and decorating models
Ornament doesn't get _really_ useful until you start _decorating_ your models.
This is done (mostly) by specifying _annotations_ (or, as of PHP7.4, type
hinting for properties) on your properties and methods.

Let's look at the simplest annotation possible: type coercion. Let's say we want
to make sure that the `id` property from the previousl example is an integer:

```php
<?php

class MyModel
{
    //...
    /** @var int */
    public $id;

    // Or, as of PHP7.4:
    public int $id;
}

//...
$model->set('id', '1');
echo $model->id; // (int)1
```

This works for all types supported by PHP's `settype` function.

## Getters and setters
Sometimes you'll want to specify your own getters and setters. No problem;
define a method and annotate it with `@get PROPERTY` or `@set PROPERTY`:

```php
<?php

class MyModel
{
    // ...

    /** @get id */
    public function exampleGetter()
    {
        // A random ID between 1 and 100.
        return rand(1, 100);
    }

    /** @set id */
    public function exampleSetter(int $id) : int
    {
        // When setting, we multiply the id by 2.
        return $id * 2;
    }
}
```

Note that a _setter_ accepts a single parameter (the thing you want to set) and
returns what you _actually_ want to set. The internal storage is further handled
by the Ornament model, so no need to worry about the details.

Getters work for public and protected properties; setters obviously only for
public properties (since protected properties are read-only).

## Decorator classes
For more complex types you can also annotate a property with a _decorator
class_. An example where this could be useful is e.g. to automatically wrap a
property containing a timezone in an instance of `Carbon\Carbon`.

Specifying a decorator class is as simple as annotating the property with
`@var CLASSNAME`:

```php
<?php

class MyModel
{
    // ...

    /**
     * @var Nesbot\Carbon\Carbon
     */
    public $date;
}
```

> Note that you _must_ use the fully qualified classname; PHP cannot know
> (well, at least not without doing scary voodoo on your sourcecode) which
> namespaces were imported.

Each Decorator class must implement the `Ornament\Core\DecoratorInterface`
interface. Usually this is done by extending `Ornament\Core\Decorator`, but it
is allowed to write your own implementation. Decorator classes are instantied
with the internal "status model" (a `StdClass`) and the name of the property to
be decorated. This allows you to access the rest of the model too, if needed
(example: a `fullname` decorated field which consists of `firstname` and
`lastname` properties). To access the underlying value, use the `getSource()`
method. Decorators also must implement a `__toString()` method to ensure
decorated properties can be safely used (e.g. in an `echo` statement). For the
abstract base decorator, this is implemented as `(string)$this->getSource()`
which is usually what you want.

It is also possible to specify extra constructor arguments for the decorator
using the `@construct` annotation. Multiple `@construct` arguments can be set;
they will be passed as the second, third etc. arguments to the decorator's
constructor. An exmaple:

```php
<?php

class MyModel
{
    // ...

    /**
     * @var SomeDecorator
     * @construct 1
     * @construct 2
     */
    public $foo;

}

class SomeDecorator extends Ornament\Core\Decorator
{
    public function __construct(StdClass $model, string $property, int $arg1, int $arg2)
    {
        // ...
    }
}
```

If your decorator gets _really_ complex and cannot be instantiated using static
arguments, one should use an `@get`ter.

> Caution: annotations are returned as either "the actual value" or, if
> multiple annotations of the same name were specific, an array. There is no
> way for Ornament to differentiate between "multiple constructor arguments"
> and "a single argument with a simple array". So internally Ornament assumes
> that if the `@construct` annotation is already an array, with an index `0`
> set, and a `count()` larger than one, you are specifying multiple
> constructor arguments. _This check will fail if you meant to specify just a
> single argument, which happens to be a simple array with multiple elements_
> (e.g. `[1, 2, 3]`).
>
> In these corner cases, just supply a second (dummy) constructor argument so
> the annotations will already be an array by the time Ornament inspects them.

## Loading and persisting models
This is your job. Wait, what? Yes, Ornament is storage engine agnostic. You may
use an RDBMS, interface with a JSON API or store your stuff in Excel files for
all we care. We believe that you shouldn't tie your models to your storage
engine.

Our personal preference is to use "repositories" that handle this. Of course,
you're free to make a base class model for yourself which implements `save()`
or `delete()` methods or whatever.

## Stateful models
Having said that, you're not completely on your own. Models may use the
`Ornament\Core\State` trait to expose some convenience methods:

- `isDirty()`: was the model changed since the last load?
- `isModified(string $property)`: specifically check if a property was modified.
- `isPristine()`: the opposite of `isDirty`.
- `markPristine()`: manually mark the model as pristine, e.g. after storing it.
  Basically this resets the initial state to the current state.

All these methods are public. You can use them in your storage logic to
determine how to proceed (e.g. skip an expensive `UPDATE` operation if the model
`isPristine()` anyway).

