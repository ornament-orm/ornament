# Collections
An Ornament Collection is an array-like object containing multiple models.
Using a Collection object has advantages:

- it offers `save`, `dirty` and `markClean` methods like models, which do what
  you'd expect
- removing an item from the collection marks it as dirty and sets up the item
  for deletion

The `Ornament\Storage::query` method returns a Collection of models. Custom
implementations of `query` are _highly_ advised to also do so.

## Usage
```php
<?php

$model = new SomeModel;
// Get collection of models where 'foo' equals 'bar':
$collection = $model->query(['foo' => 'bar']);
// Let's say this has 4 entries:
$collection[0]->baz = 'buz';
var_dump($collection->dirty()); // true
$collection->save();
var_dump($collection->dirty()); // false
unset($collection[2]);
$collection->save();
// Now there are only 3 entries!
```

