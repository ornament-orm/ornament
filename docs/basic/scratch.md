# Writing Ornament models from scratch
Before we dive into anything complicated, let's start by writing an Ornament
model from scratch. We'll begin with a model only storing stuff in a PDO
database:

```php
<?php

use Ornament\Pdo;

class MyModel
{
    use Pdo;

    public $id;
    public $name;
    public $comment;

    public function __construct(PDO $pdo)
    {
        $this->addPdoAdapter($pdo, 'mytable', ['name', 'comment'])
             ->setPrimaryKey('id');
    }
}

$pdo = new PDO('dsn', 'user', 'pass');
$model = new MyModel($pdo);
$model->name = 'Marijn';
$model->comment = 'Ohai Ornament!';
$model->save();
```

That's really all you need to get started. But let's look a bit deeper into
that, because this all still looks like magic.

In the model constructor, we call `addPdoAdapter` with our `PDO` object as the
first argument, the table as second and finally an array of fields. On the
return value of that (a wrapped `Ornament\Adapter` object) we then tell
Ornament how to handle this type of object; we call `setPrimaryKey` to tell the
PDO adapter what the primary key field is.

> In this example, the `PDO` object is injected via the constructor, but of
> course you can get it from wherever you like.

If only one primary key is defined, Ornament assumes (at least, for `Pdo`
adapters) it is an `AUTO_INCREMENT`-type column and it can call `lastInsertId`
to retrieve the generated value. The `Pdo` adapter then reloads the model
based on your primary key(s).

> Reloading will not always be necessary, but tables might specify `BEFORE
> UPDATE` triggers or use other methods of setting default values for empty
> fields (like `NOW()` for a timestamp). We want that data! It's just one
> extra select after an otherwise already expensive operation.

## Updating
Building on the previous example, we can also _update_ our `$model` object:

```php
<?php

// ...as before...
$model->comment = 'Hey this is awesome!';
$model->save();
```

## Deleting
Or we can delete it:

```php
<?php

// ...as before...
$model->delete();
```

## Good programmers are lazy
Well, at least _I_ am. Ornament `Pdo` models do make a number of assumptions
based on the model you call `addPdoAdapter` on:

- The `fields` are the public members;
- If there is a field named `id`, it's probably a primary key;
- You can register a callback to guesstimate table names as well.

Let's simplify `MyModel` even further:

```php
<?php

use Ornament\Pdo;

class MyModel
{
    use Pdo;

    public $id;
    public $name;
    public $comment;

    public function __construct(PDO $pdo)
    {
        $this->guessTableName(function ($class) {
            $class = preg_replace('@\\\\?Model$@', '', $class);
            $table = strtolower(preg_replace_callback(
                '@([a-z0-9])(_|\\\\)?([A-Z])@',
                function ($match) {
                    return $match[1].'_'.strtolower($match[3]);
                },
                $class
            ));
            return trim($table, '_');
        });
        $this->addPdoAdapter($pdo);
    }
}
```

We now have slightly more code, but since this is a common scenario you could
(should) abstract it away, maybe to a base class. Actually, the above example is
Ornament's PDO adapter's default implementation so we could have left it out all
together.

> Yes, we specifically said Ornament didn't make these assumptions unlike e.g.
> Eloquent, but if your database is well designed they'll come in handy for
> simpler models. And you can always override using the above logic.

The second argument we said was the "table" is actually rather an "id" for
Ornament to differentiate by when multiple adapters are registered. You'll
learn more about this later.

For non-PDO Adapters (more about those later as well) one should also assume
all public properties are "the fields" in the model.
