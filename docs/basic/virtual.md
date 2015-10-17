# Adding virtual fields
Besides exposing fields that are mapped directly to a datasource, Ornament
models can also expose so-called "virtual fields". A virtual field is simply
a value you can get and/or set, but which is persisted to the storage backend
in another way. Two common uses of this are:

1. Virtual boolean properies which are actually stored in a bitflag;
2. One-to-many foreign key relations which get filled in automatically.

## Defining a virtual field
Virtual fields are supported out-of-the-box. To use them, simply declare
methods called `getNameOfField` and possibly `setNameOfField` (for read/write
virtual properties) on your model:

```php
<?php

class MyModel
{
    public function getNameOfField()
    {
        return 1; // Obviously, this needs to be computed.
    }

    public function setNameOfField($value)
    {
        // This should re-compute.
    }
}
```

The `nameOfField` part of the methods map following the same logic as the
default automagic table guesser, i.e. `nameOfField` becomes `name_of_field` as
a property name.

> This is done to reflect the fact that whilst modern PHP applications prefer
> the camelCased style for properties, data sources generally don't (this goes
> for SQL databases as well as NoSQL, APIs and even XML or CSV data sources).

## Example with bitflags
Assume we have a model with a `status` member defined as an integer. We can now
store multiple `true`/`false` flags in that field. E.g. we could use `1` to
signify "this person is nice", `2` for "person owns cats" and `4` for "person
writes code". An example model could look like this:

```php
<?php

class UserModel
{
    use Ornament\Model;

    const STATUS_NICE = 1;
    const STATUS_CATS = 2;
    const STATUS_CODE = 4;

    public $id;
    public $name;
    public $status;

    public function __construct(PDO $pdo)
    {
        $this->addAdapter(new Ornament\Adapter\Pdo($pdo));
    }

    public function getNice()
    {
        return $this->status & self::STATUS_NICE;
    }

    public function setNice($value)
    {
        if ($value) {
            $this->status |= self::STATUS_NICE;
        } else {
            $this->status &= ~self::STATUS_NICE;
        }
    }

    // etc.
}

$user = new UserModel;
$user->name = 'Marijn';
$user->nice = true;
```

This is a very common scenario, so Ornament offers a `Bitflag` class to
simplify this task for you:

```php
<?php

class UserModel
{
    use Ornament\Model;

    public $id;
    public $name;
    /** @Bitflag nice = 1, cats = 2, code = 4 */
    public $status;

    public function __construct(PDO $pdo)
    {
        $this->addAdapter(new Ornament\Adapter\Pdo($pdo));
    }
}
```

Now, the `status` property is itself an object that handles bitwise operations:

```php
<?php

$user->status->nice = true;
```
These calls accomplish the same as the manual getter/setter from the previous
example, only in much less lines of code. Which is good, because we're lazy.

A `Bitflag` can also be `__toString`ed yielding the underlying integer value
(obviously cast to a string value though).

