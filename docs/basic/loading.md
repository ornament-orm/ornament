# Getting data into your models
Querying data isn't really Ornament's job, we think. _Say what?_ Well, the 'M'
in ORM stands for 'mapper' after all - its job is to _map_ data to your models,
not to actually _retrieve_ it. (Otherwise they'd have called it "Object
Mapper-Getter", which admittedly would have made for a cute acronym.)

Having said that, however, adapters do need to implement a `load` method. You
can utilise that to facilitate a form of autoloading.

```php
<?php

use Ornament\Pdo;

class SimpleModel
{
    use Pdo;

    public $id;
    public $foo;

    public function __construct(PDO $pdo)
    {
        $this->addPdoAdapter($pdo);
    }
}

$model = new SimpleModel($pdo);
$model->id = 1;
$model->load();
```

## Querying a model's adapter
The above obviously only works on single models, and only if you happen to know
the primary key.

Models can implement the `Ornament\Query` trait. This gives them access to more
powerful querying utilities:

```
<?php

use Ornament\Pdo;
use Ornament\Query;

class SimpleModel
{
    use Pdo;
    use Query;

    // ...etc...
}
```

To load a single model, use the static `find` method:

```php
<?php

$model = SimpleModel::find(['foo' => 'bar']);
```

The first argument to `find` is some type of identifier for the model we want to
retrieve. For the PDO adapter, it's a hash of key/value pairs used to construct
the `WHERE` parts of the query. The above example would evaluate to 
`where foo = 'bar'`. Other adapters might support more (or less) powerful
options you can pass in. E.g., an adapter for an API might just expect a single
value (the `id` you need to pass to the endpoint).

> We agree that conceptually it would be cleaner to use a _different_ class for
> retrieving models, cfg. Doctrine's Repositories. However, this would also mean
> duplicating all kinds of logic and that's a bad tradeoff. By using a Trait, we
> can simply reuse the adapter logic already defined on the model.

## Loading an array of models (a Collection)
The Query trait also provides a static `query` method which returns an array of
models matching the `$where` parameter:

```php
<?php

$list = SimpleModel::query(['foo' => 'bar']);
```

`$list` in this example is actually an `Ornament\Collection` object. Collections
behave like arrays, but contain additional functionaliy (e.g. `isDirty` on the
entire collection).

## Custom collections
Often (especially on more complex projects) you'll want to predefine an
interface or `query` methods. For instance, for a `UserModel` you might need to
query active users (by your own definition), logged in users, bad users and
premium users. Remembering the exact `$where` clause is cumbersoms and besides
not very DRY. Your models should simply define their own static methods passing
the correct parameters to `self::query`:

```php
<?php

class SimpleModel
{
    // etc.

    public static function queryActive()
    {
        return self::query(['active' => true]);
    }
}
```

Note that you in no way _need_ to forward to `self::query`; as long as your
custom method returns a `Collection` (just instantiate with an array of models
as a constructor argument) you're good to go.

## Doctrine-like repositories
You can also go out of your way and place all `query`-related methods in a
separate class, and _still_ benefit from the setup already done:

```php
<?php

abstract class SimpleService extends SimpleModel
{
    // We move this here:
    use Ornament\Query;

    // Static query methods etc.
}
```

Ornament will see that the `SimpleService` is `abstract` and extends a
`SimpleModel`, and know to use the latter when constructing models. This way,
for complex models with lots of `query` types, you can abstract them away into
the Service class and keep the model itself clean.

## Auto-loading relationships
Often you'll want to automagically load related objects, e.g. when an
`ItemModel` has an `owner` propery that is actually a `UserModel`. For this,
your models can use the `Autoload` trait and you should annotate your properties
accordingly:

```php
<?php

class ItemModel
{
    use Ornament\Pdo;
    use Ornament\Autoload;

    /**
     * @Model UserModel
     * @Mapping id = owner
     * @Constructor [ pdo ]
     */
    public $owner;

    // etc
}
```

The default `@Mapping` is "map autoloaded fieldname to id on the new model",
which normally makes sense. You only have to explicitly specify this if your
mapping is different (which it shouldn't be for 99% of the cases).

The `@Constructor` annotation lets you pass properties on `$this` (the calling
model) to the constructor of the child model. This is useful for single-adapter
projects; if your project grows and starts mixing adapters, you should consider
[dependency injection](http://disclosure.monomelodies.nl) for this and use
argument-less constructors.

## Auto-loading one-to-many relationships
The reverse can also happen: for each `UserModel`, you want all `ItemModel`s
she "owns". This is similar; you only need to set the field's default value to
an empty array:

```php
<?php

class UserModel
{
    use Ornament\Pdo;
    use Ornament\Autoload;

    /**
     * @Model ItemModel
     * @Mapping owner = id
     * @Constructor [ pdo ]
     */
    public $items = [];

    // etc
}
```

> Ornament is smart enough to prevent infinite loops, but one should still take
> care when autoloading many relationships; the number of queries done can
> quickly grow out of control.

## Manual loading
Of course, you don't _need_ to use this; in fact, optimized queries are often
too complicated to wrap in such an abstraction (the above is just quick and
dirty for simpler models). Or you might be using something like Doctrine's
repositories for this.

Simply write your own `load` and `query` implementations. `load` should update
the current model with data as read from source, whilst `query` should return
an array populated with instances of `__CLASS__` according to the specified
parameters/options.

The function signature for `query` is:

```php
public function query($where, array $opts = [], array $ctor = []);
```

`$opts` is a hash of adapter-specific options. For instance, the default Pdo
adapter supports `limit(int)`, `offset(int)` and `order(string)`.

`$ctor` is an optional array of constructor arguments your implementation should
use when instantiating the new models in the list.

In the function body, simply do whatever you need to do to get your data, and
then loop through it creating a new instance of `__CLASS__` with the correct
properties set.

There's also a `find` method which is shorthand for `query(...)[0]` and takes
the same arguments.

You can also avoid using `query` and `find` alltogether; keep in mind though
that internally Ornament will still be using them, so for consistency it's
usually best to override them.

