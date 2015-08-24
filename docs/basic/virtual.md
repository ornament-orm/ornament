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

use Ornament\Pdo;

class UserModel
{
    use Pdo;

    const STATUS_NICE = 1;
    const STATUS_CATS = 2;
    const STATUS_CODE = 4;

    public $id;
    public $name;
    public $status;

    public function __construct(PDO $pdo)
    {
        $this->addPdoAdapter($pdo);
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

This is a very common scenario, so Ornament offers the `Bitflag` trait to
simplify this task for you:

```php
<?php

use Ornament\Pdo;

class UserModel
{
    use Pdo;

    const STATUS_NICE = 1;
    const STATUS_CATS = 2;
    const STATUS_CODE = 4;

    public $id;
    public $name;
    public $status;

    public function __construct(PDO $pdo)
    {
        $this->addPdoAdapter($pdo);
        $this->addBitflag('nice', self::STATUS_NICE, 'status');
        $this->addBitflag('cats', self::STATUS_CATS, 'status');
        $this->addBitflag('code', self::STATUS_CODE, 'status');
    }
}
```

These calls do the same as the manual getter/setter from the previous example,
only in much less lines of code. Which is good, because we're lazy. The
arguments are `property`, `bit` and `source property`.

## Example with other models (one-to-one or one-to-many relationships)
Apart from the simplest of projects, you'll usually want to mix and match models
to create compound object. For instance, imagine an `Item` model which has a
number of `images` attached, one of which is the `thumbnail`:

```php
<?php

use Ornament\Pdo;

class ItemModel
{
    public $id;
    public $thumbnail;
    public $images = [];

    public function __construct(PDO $pdo)
    {
         // We manually specify $fields here, since the $images array
         // comes from elsewhere.
        $this->addPdoAdapter($pdo, null, ['thumbnail']);
        $stmt = $pdo->prepare("SELECT * FROM image WHERE item = ?");
        $stmt->execute([$this->id]);
        $this->images = $stmt->fetchAll(PDO::FETCH_CLASS, 'ImageModel');
        if ($this->images && $this->thumbnail) {
            array_map(function ($image) {
                if ($image->id == $this->thumbnail) {
                    $this->thumbnail = $image;
                }
            });
        }
    }
}

class ImageModel
{
    public $id;
    public $url;

    public function __construct(PDO $pdo)
    {
        $this->addPdoAdapter($pdo);
    }
}

$stmt = $pdo-prepare("SELECT * FROM item WHERE id = ?");
$stmt->execute([1]);
$item = $stmt->fetchObject('ItemModel', [$pdo]);
```

`save` calls elegantly cascade, so you can change anything you like to the
`$item` object (including properties on `thumbnail` or one of the `images`!) and
Ornament will take care of saving all changes. As an added bonus, it will _only_
attempt to save if the model is considered "dirty".

## Adding or removing from array-type virtual fields
In the above example, let's say our `$item` has 4 images already. We now want to
add a fifth one, and remove the first. It's as easy as you'd expect:

```
<?php

// Add a new image:
$newImage = new ImageModel;
$newImage->url = 'http://example.com/path/to/image.jpg';
$item->images[] = $newImage;

// Delete the first image:
$first = array_shift($item->images);
$first->delete();
```

Physically removing the model from the array is not required for deletion, but
if you don't it'll just linger around.

## Updating indices
Sometimes you'll want your models to use something akin to a `position` field
to signify their order in the list. E.g. if we want the owner of our `$item` to
be able to manually define the order of the images.

Easy: specify an `__index` method on your model. This gets called automatically
with the current index on saving (so that's also what makes it a good idea to
actually _remove_ a model from an array before saving - `delete` can't do that
for you).

```php
<?php

class ImageModel
{
    public $id;
    public $url;
    public $position;

    public function __construct(PDO $pdo)
    {
        $this->addPdoAdapter($pdo);
    }

    public function __index($index)
    {
        // Assume we want to store $position 1-indexed as opposed to
        // 0-indexed.
        $this->position = $index + 1;
    }
}
```

