# Writing Ornament models from scratch
Before we dive into anything complicated, let's start by writing an Ornament
model from scratch. We'll begin with a model only storing stuff in a PDO
database:

```php
<?php

use Ornament\Model;
use Ornament\Adapter;

class MyModel
{
    use Model;

    public $id;
    public $name;
    public $comment;

    public function __construct(PDO $pdo)
    {
        $this->addAdapter(new Adapter\Pdo($pdo), 'mytable', ['name', 'comment'])
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

In the model constructor, we call `addAdapter` with three arguments:
1. An instance of an `Ornament\Adapter` that wraps our actual connection, in
   this case a `PDO` object;
2. An "identifier" for this Model. In this case, we use the table name;
3. The properties on the Model that are handled by this adapter.

On the return value of that (a wrapped `Ornament\Adapter` object) we then tell
Ornament how to handle this type of object; we call `setPrimaryKey` to tell the
PDO adapter what the primary key field is.

> In this example, the `PDO` object is injected via the constructor, but of
> course you can get it from wherever you like.

If only one primary key is defined, Ornament assumes (at least, for `Pdo`
adapters) it is an `AUTO_INCREMENT`-type column and it can call `lastInsertId`
to retrieve the generated value. The `Pdo` adapter then reloads the model
based on your primary key(s). Non-PDO adapters need to implement their own logic
here; e.g. for an API it is common to return a created object with the new ID,
so the adapter should use that instead.

> Reloading will not always be necessary, but tables might specify `BEFORE
> UPDATE` triggers or use other methods of setting default values for empty
> fields (like `NOW()` for a timestamp). We want that data! It's just one
> extra select after an otherwise already expensive operation.
>
> Non-PDO adapters will use other methods of reloading; e.g. an API should
> return the new object on create or update, so that could be utilised.

Instead of calling methods on adapters (which may or may not exist), you can
also use _annotation_ to tell Ornament what to do:

```php
<?php

/**
 * @Identifier table_alias
 */
class MyModel
{
    use Ornament\Model;

    /** @PrimaryKey */
    public $id;
    public $name;
    /** * @Virtual */
    public $something_our_query_calculates;

    // etc
}
```

On the Model, specify the annotation `@Identifier` to hardcode the identifier
(table name, in the case of the `Pdo` adapter). Properties can be annotated in
multiple ways (more on which later), but in this example we use `@PrimaryKey`
to specify what our primary key is (note that Ornament guesses a property `$id`
will be a primary key anyway, but for clarity's sake it's better to explicitly
annotate or set), and `@Virtual` to tell Ornament that the property
`$something_our_query_calculates` exists on the model, but is not something it
should attempt to persist.

Any property whose name begins with an _underscore_ is considered "internal" and
won't be persisted.

> Ornament internally sets some "really internal properties" prefixed with a
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
based on the model you call `addAdapter` on:

- The `fields` are the public members;
- If there is a field named `id`, it's probably a primary key;
- You can register a callback to guesstimate table names as well.

Let's simplify `MyModel` even further:

```php
<?php

class MyModel
{
    use Ornament\Model;

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
        $this->addAdapter(new Ornament\Adapter\Pdo($pdo));
    }
}
```

We now have slightly more code, but since this is a common scenario you could
(should) abstract it away, maybe to a base class. Actually, the above example is
Ornament's `Pdo` adapter's default implementation so we could have left it out
all together.

> Yes, we specifically said Ornament didn't make these assumptions unlike e.g.
> Eloquent, but if your database is well designed they'll come in handy for
> simpler models. And you can always override using the above logic.

The second argument we said was the "table" is actually rather an "id" for
Ornament to differentiate by when multiple adapters are registered. You'll
learn more about this later.

For non-PDO Adapters (more about those later as well) one should also assume
all public properties are "the editable fields" in the model.

## Protected and private properties
What if we have query results that we _do_ want to expose, but in a read-only
manner? Simple: declare them `protected` or `private` (doesn't matter which) and
use the `Ornament\Virtual` trait to expose them:

```php
<?php

class MyModel
{
    use Ornament\Model;
    use Ornament\Virtual;

    // id is now read-only:
    protected $id;
}
```

> The `Virtual` trait automatically exposes private and protected members as
> read-only properties. If you have a member that should _really_ be invisible,
> prefix its name with an underscore. Ornament will skip it. Or, alternatively,
> annotate it with `@Private`.
>
> If you specify a `getPropertyName` accessor, this will be called instead
> (useful if your private property needs some operation done before being
> exposed). Similarly, `setPropertyName` automatically is called when the
> property is changed from the outside. _This_ is useful if setting should
> trigger a more complicated operation/calculation instead of a simple
> `prop = value` operation.

`PDO`-type models will have their members set _before_ construction due to,
well, PDO-weirdness to be honest. But this is how Ornament decides if a model
instance is "new" or not. Other adapters will need to implement their own logic
for this.

