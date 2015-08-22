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

