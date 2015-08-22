# Adding virtual fields
Besides exposing fields that are mapped directly to a datasource, Ornament
models can also expose so-called "virtual fields". A virtual field is simply
a value you can get and/or set, but which is persisted to the storage backend
in another way. Two common uses of this are:

1. Virtual boolean properies which are actually stored in a bitflag;
2. One-to-many foreign key relations which get filled in automatically.

## Defining a virtual field
`use` the `Ornament\Virtual` trait and, in your constructor, call the new
`addVirtual` method this exposes:

```php
<?php

use Ornament\Virtual;

class MyModel
{
    public function __construct(PDO $pdo)
    {
        $this->addAdapter($pdo);
        $this->addVirtual(
            'virtual_field',
            function ($value) {
                // Do what you need with $value
                return $value;
            }
        );
    }
}
```

The `addVirtual` method takes two arguments: a string with the name of the
virtual field, and a function that acts as a setter. This function should
return the actual value to set.

