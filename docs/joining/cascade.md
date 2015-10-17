# Cascading queries
Often though, your data relationships aren't a question of joins, but rather of
"linked" models - like in our second example from the previous chapter: an
`item` that has multiple `images` (or, inversely, an `image` that has an
`owner`).

## Example with other models (one-to-one or one-to-many relationships)
Our second example is not a straight join, but should rather instruct Ornament
to "cascade" the models into sub-models. Well, it can do that:

```php
<?php

class ItemModel
{
    use Ornament\Model;
    use Ornament\Autoload;
    use Ornament\Query;

    public $id;
    /**
     * @Model ImageModel
     */
    public $thumbnail;
    /**
     * @Model ImageModel
     * @Mapping item = id
     */
    public $images = [];

    public function __construct(PDO $pdo)
    {
        $this->addAdapter($pdo);
    }
}

class ImageModel
{
    use Ornament\Model;

    public $id;
    public $url;

    public function __construct(PDO $pdo)
    {
        $this->addAdapter($pdo);
    }
}

// Assuming we have an `item` with id 1 and 4 images, one of which is
// the thumbnail:
$item = ItemModel::find(['id' => 1]);
var_dump($item->thumbnail instanceof ImageModel); // true
var_dump(count($item->images)); // 4

```

The `$thumbnail` property is annotated with a `@Model`. This is the sub-model
we want Ornament to load instead of the "flat" integer value. The default quess
to load the sub-model with an `id` property that matches the property value (in
this case, `thumbnail`). You can optionally annotate with `@Mapping` to override
this behavious. `@Mapping` takes a list of key/value pairs, where the keys are
the property that should match on the sub-model, and the values are the property
whose value on the parent model is used. Hence, for the `ImageModel` we could
specify `@Mapping id = thumbnail` to mimic the default behaviour.

The `$images` property is also annotated with a `@Model`, but since it is an
array Ornament will automatically created a one-to-many relationship.

> Models retrieved using `find` are automatically given their related models.
> For models retrieved using `query` you should call `load` manually on each
> entry in the Collection if you need sub-models. The idea here is to avoid
> doing lots of potentially unnecessary queries when retrieving lists.

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
$item->save(); // $newImage is now persited.

// Delete the first image:
$first = array_shift($item->images);
$item->save(); // First image is now deleted.
// ...or:
$item->images[0]->delete(); // First image is now deleted.
```

Physically removing the model from the array is not required for deletion, but
if you don't it'll just linger around.

> Autoloaded arrays of submodels on models aren't actually arrays, but rather an
> instance of [Ornament\Collection](../advanced/collection.md). Collections have
> some interesting properties, but suffice to say they extend `SplObjectStorage`
> with augmentations to allow numeric indices as well. So to all intents and
> purposes, they mostly behave like arrays in every day use.

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

