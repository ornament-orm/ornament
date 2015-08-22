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

The `addVirtual` method takes two arguments: a string with the name of the
virtual field, and a function that acts as a setter. This function should
return the actual value to set.

