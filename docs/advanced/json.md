# Exporting to Json
An Ornament model will have all sorts of data floating around you won't need
when you need to pass it to another service, adapter or whatever. Luckily PHP
offers a `JsonSerializable` interface for this. And of course we offer a default
for this.

## Step 1: implement the interface
```php
<?php

class MyModel implements JsonSerializable
{
    //...
}
```

Well, that was easy. The interface requires you to implement a public method
called `jsonSerialize` which returns the JSON to be serialized (i.e., something
devoid of internal members).

## Step 2: install the `Ornament\Json` dependency
```sh
$ composer require ornament/json
```

## Step 3: use the `Ornament\Json\Model` trait instead of `Ornament\Ornament\Model`

```php
<?php

use Ornament\Json\Model;

class MyModel implements JsonSerializable
{
    use Model;
    //...
}
```

The Json Model adds a `jsonSerialize` method which "flattens" your model object
to a PHP `StdClass`. It does this recursively, i.e. sub-models are also
flattened (as long as they support it), and Collections are converted to regular
arrays. Any bitflags are also converted to standard classes.

Of course, for complicated exports you can also implement your own serialization
logic.

> Json serialization would only be needed if you need to pass your models to
> another, non-PHP environment, e.g. to Javascript via DNode.

