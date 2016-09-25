# Ornament
PHP5 ORM toolkit

ORM is a fickle beast. Many libraries (e.g. Propel, Doctrine, Eloquent etc)
assume your database should correspond to your models. This is simply not the
case; models contain business logic and may, may not or may in part refer to
database tables, NoSQL databases, flat files, an external API or whatever. The
point is: the models shouldn't care, and there should be no "conventional"
mapping through their names. (A common example would be a model of pages in
multiple languages, where the data might be stored in a `page` table and a
`page_i18n` table for the language-specific data.)

Also, the use of extensive and/or complicated config files sucks. (XML? This
is 2015, people!)

## Installation

### Composer (recommended)
Add "monomelodies/ornament" to your `composer.json` requirements:

```bash
$ composer require monomelodies/ornament
```

### Manual installation
1. Get the code;
    1. Clone the repository, e.g. from GitHub;
    2. Download the ZIP (e.g. from Github) and extract.
2. Make your project recognize Ornament:
    1. Register `/path/to/ornament/src` for the namespace `Ornament\\` in your
       PSR-4 autoloader (recommended);
    2. Alternatively, manually `include` the files you need.

## Basic usage
Ornament models (or "entities" if you're used to Doctrine-speak) are really
nothing more than vanilla PHP classes; there is no need to extend any base
object of sorts (since you might want to do that in your own framework!).

Ornament is a _toolkit_, so it supplies a number of `Trait`s one can `use` to
extend your models' behaviour beyond the ordinary.

The most basic implementation would look as follows:

```php
<?php

use Ornament\Ornament\Model;

class MyModel
{
    // The generic Model trait that bootstraps this class as an Ornament model;
    // it contains basic functionality.
    use Model;

    // Public properties on a Model are considered "read/write" by Ornament:
    public $id;
    public $name;
    // Protected properties are read-only:
    protected $value;
    // Private properties are just that: private and of no concern to Ornament.
    private $pdo;
}

```

The `Model` trait defines magic getters and setters to access decorated
properties. It also defines a default constructor calling the protected
`ornamentalize` method on the model. If you need your own constructor you must
call this manually in there:

```php
<?php

use Ornament\Ornament\Model;

class MyModel
{
    use Model;
    // ...define properties...

    public function __construct()
    {
        // Do work...
        // Finally:
        $this->ornamentalize();
        // Or maybe do more work afterwards?
    }
}

```

The `ornamentalize` method sets up your model's ORM magic:

- All existing properties on the model are unset and their values privately
  stored. This way whenever you access anything it is handled by the magic
  getters/setters.
- If the model has "state" prior to `ornamentalize` being called, it is
  considered to "exist" already. Otherwise, it is considered "new".

## Getting data into your model
This is up to you. _Say what?_ Well, Ornament is a _mapper_, not a query
builder or anything like that. Use whatever tool you like to get your data.

For instance, using `PDO` you could `fetchObject`:

```php
<?php

$stmt = $pdo->prepare("SELECT * FROM your_table");
$stmt->execute();
$model = $stmt->fetchObject(MyModel::class);

```

`PDOStatement`'s `fetchObject` method has the curious behaviour that all
selected fields are set as properties on the object _prior_ to construction,
and it will also work for protected/private properties.

If your data source can't do that (e.g. because you're loading from an API via
HTTP) you should write your own constructor and call `ornamentalize` manually
whenever you're ready.

## Models with multiple data sources
There's also an alternative: the `addDatasource` method. This takes any object
as its argument and inspects its public properties. These are then exposed via
the magic getters/setters. So the previous example could also be written as:

```php
<?php

$stmt = $pdo->prepare("SELECT * FROM your_table");
$stmt->execute();
$model = new MyModel;
$model->addDatasource((object)$stmt->fetch());

```

Note that this ignores any non-string properties, so you can safely call fetch
without specifying `PDO::FETCH_ASSOC`.

Using `addDatasource` allows you to specify multiple sources. We'll see how this
comes in handy later on. For convenience, you can also call `fromDatasource`
statically on your model:

```php
<?php
$stmt = $pdo->prepare("SELECT * FROM your_table");
$stmt->execute();
$model = MyModel::fromDatasource((object)$stmt->fetch());

```

Note that both `addDatasource` and `fromDatasource` "reset" the model's state,
i.e. when called the model is assumed to be "existing" afterwards, not "new".

## Persisting models
Again, this is your job since we don't want to tell you where to store your
data. Commonly you would add `save` and `delete` methods to your models, but
really we don't care.

The `Model` trait supplies (non-implemented) `save` and `delete` methods. If you
call them without supplying an actual implementation, an error is triggered.

An alternative option is to offload the persistance logic to a "repository" and
not have the model worry about it at all.

## Annotating and decorating properties
If a `getPropertyName` (corresponding to `$model->property_name`) method exists
on the model, Ornament will call that method to retrieve a property. Similarly,
a `setPropertyName` method could exist for setting the property. These are
simple examples of low level _decorators_.

For recurring, more generic decorations you can use _annotations_ in combination
with decoration-supplying traits. Decorations are applied in order (or in
reverse order when setting) and you can have as many as you want (though more
than one or two will only rarely make sense). See the `ornament/bitflag`
package for a real world example.

If a decorator mutates the value into an object (like the `Bitflag` decoration)
on setting it is either the result of `__invoke` or else `__toString`. It is
automatically converted to a scalar prior to passing it to the next decorator.

When `get`ters or `set`ters exist on the model, these are called _instead_ of
the default logic. Hence, a decoration on a property will _not_ do anything in
this case (you are of course free to define _either_ a getter or a setter, in
which case decorations _will_ get applied depending on which one is absent).

The idea is that if you specifically define getters or setters, you're going to
want to do something special. E.g., if you have properties `firstname` and
`lastname` you might also want a "virtual" property `fullname` which
concatenates the two, but decorating this doesn't generally make sense. If you
really need to morph into a Decorator object, you could just return it from the
method.

## Related models
Using the provided `@Model` decorator, you can quickly build a tree of related
models. The parameter is the classname of the relation, and it will be
constructed with the field's original value as an argument. It is the related
model's responsibility to correctly instantiate itself based on this parameter
_and_ to correctly supply `__invoke` or `__toString` methods.

If the default value for an `@Model` annotated property is an _array_, Ornament
will return a `Collection` instead. Collections are array-like objects which
also have an `isDirty` method (true if any of the models in it can be considered
dirty) and can be saved/deleted like models.

