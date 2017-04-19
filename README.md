# Ornament
PHP5 ORM toolkit

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
is 2015, people!)

## Installation

### Composer (recommended)
Add Ornament to your `composer.json` dependencies:

```sh
$ composer require ornament/ornament
```

### Manual installation
1. Clone or download the repository;
2. Register `/path/to/ornament/src` for the namespace `Ornament\\Ornament\\`in
   your PSR-4 autoloader;

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
    use Model;

    // All protected properties on a model are considered "handleable" by
    // Ornament. They can be decorated (see below) and are exposed for getting
    // and setting via the Model API:
    protected $id;
    // Public properties are also potentially handleable, but they cannot
    // be decorated and can only be used verbatim:
    public $name;
    // Private properties are just that: private. They're left alone:
    private $pdo;

    public function __construct($id)
    {
        $this->id = $id;
    }
}

$model = new MyModel(1);
$model->isNew(); // true
$model->name = 'Marijn';
$model->isDirty(); // true
$model->isPristine(); // false
$model->isClean(); // false
$model->name = null;
$model->isClean(); // true
```

## Annotating and decorating models
Ornament models work by decorating the original object and its properties. This
is done (mostly) by specifying _annotations_ on your properties. Models
implementing the corresponding interfaces are then automatically recognised:

```php
<?php

use Ornament\Ornament\Model;
use Ornament\Bitflag;

class MyModel implements Bitflag\Decorator
{
    use Model;

    /** @Bitflag foo = 1, bar = 2 */
    protected $status;
}

$model = new MyModel;
$model->status instanceof Bitflag\Property; // true
```

There are a number of decorators specified in the various Ornament subpackages.
Of course you're also free to create your own, as long as they implement the
`Ornament\Ornament\Decorator` interface.

## Working with decorated properties
Decorated properties are objects implementing the `Ornament\Ornament\Decorator`
interface. To access their "raw" values, one can `__toString` them:

```php
<?php

// ...with the bitflag from the earlier example:
$model->status->bar = true;
echo $model->status; // "2"
```

> For more information on bitflags, see the readme for the `ornament/bitflag`
> package.

## Adding adapters
Whilst you could very well extract information from your models and persist them
somewhere yourself, it is of course handier to let Ornament do that for you. To
accomplish this, we introduce the concept of _adapters_.

An Adapter is an interface to a storage platform, e.g. a database or a REST
service. The idea is for your models to not have to care what is stored where,
and rather let the adapter(s) take care of that.

As an example, let's first install Ornament's `Pdo` adapter:

```sh
$ composer require ornament/pdo
```

Next, `use` the `Ornament\Ornament\Persist` trait and add an instance of your
adapter:

```php
<?php

use Ornament\Ornament\Model;
use Ornament\Ornament\Persist;

class SomeTableModel
{
    use Model;
    use Persist;

    protected $id;
    public $name;

    public function __construct()
    {
        global $pdo;
        $this->addAdapter(new Adapter($pdo));
    }
}

$stmt = $pdo->query("SELECT * FROM some_table");
$stmt->setFetchMode(PDO::FETCH_CLASS, 'MyModel');
$model = $stmt->fetch();
```

On construction, the model maps its properties using the corresponding adapter.
As it is now `Persist`able, we have some additional methods available:

```php
<?php

$model->name = 'Marijn';
$model->save(); // Persist back into the database! (`insert` or `update`)

$model->delete(); // Delete it from the database.
```

In the `ornament/marker` subpackage, there is also a `Persistable` interface you
can use in code for checking (`$model instanceof Persistable`).

