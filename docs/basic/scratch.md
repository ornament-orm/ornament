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

Instead of calling methods on adapters (which may or may not exist), you can
also use _annotation_ to tell Ornament what to do:

```php
<?php

/**
 * @Identifier table_alias
 */
class MyModel
{
    use Pdo;

    /**
     * @PrimaryKey
     */
    public $id;
    public $name;
    /**
     * @Virtual
     */
    private $something_our_query_calculates;

    // etc
}
```

On the Model, specify the annotation `@Identifier` to hardcode the identifier
(table name, in the case of the Pdo adapter). Properties can be annotated in
multiple ways (more on which later), but in this example we use `@PrimaryKey`
to specify what our primary key is (note that Ornament guesses a property `$id`
will be a primary key anyway, but for clarity's sake it's better to explicitly
annotate or set), and `@Virtual` to tell Ornament that the property
`$something_our_query_calculates` exists on the model, but is not something it
should attempt to persist. Also, by defining the property as `private` it
automatically becomes read-only (which makes sense for a generated value).

> The `Storage` trait automatically exposes private and protected members as
> read-only properties. If you have a member that should _really_ be invisible,
> prefix its name with an underscore. Ornament will skip it. If you specify a
> `getPropertyName` accessor, this will be called instead (useful if your
> private property needs some operation done before being exposed). Similarly,
> `setPropertyName` automatically is called when the property is changed from
> the outside. _This_ is useful if setting should trigger a more complicated
> operation/calculation instead of a simple `prop = value` operation.

> Ornament internally sets some "really private properties" prefixed with a
> double underscore. To avoid conflicts, it's best to avoid those in your
> models.

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
        $this->guessIdentifier(function ($class) {
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
all public properties are "the editable fields" in the model. This is
automatically augmented with fields defining a setter - unless any of them
are specifically marked `@Virtual`, or they are marked as referencing an
external model (see the chapter on getting data).

