# Writing your own Adapters
Writing your own adapters for Ornament models is actually very easy. There's
only three steps involved:

1. Define an `Adapter` class that implements the `Ornament\Adapter` interface;
2. Optionally give consuming models a `addMyadapterAdapter` method;
3. Inject and load it where needed.

## Writing the actual adapter class
The constructor should take three arguments:

1. The actual adapter object;
2. A unique identifier for this type of model;
3. An array of properties that are to be associated with this adapter.

For the PDO adapter shipped with Ornament, the identifier is the table name and
the property list are the fields for that table. If your adapter for instance
communicates with a REST API, the identifier might be the endpoint for this type
of model. E.g.:

```php
<?php

use Ornament\Adapter;

class MyAwesomeApiAdapter implements Adapter
{
    private $host = 'https://api.example.com';
    private $endpoint;

    public function __construct($endpoint, array $fields)
    {
        $this->endpoint = $endpoint;
        $this->fields = $fields;
    }
}
```

Then, define the three methods required by the `Adapter` interface: `create`,
`update` and `delete`. Each takes a single argument: an Ornament `Model`, which
is kind of an internal data type.

These methods do exactly what their names imply: CRUD operations on whatever
your data source is. The implementation is entirely up to you. They _should_
return `true` on success and `false` on failure for neatness' sake, but this
isn't required.

## Adding the adapter to your models
Now you can instantiate your adapter with whatever parameters you need and add
it to your models.

```php
<?php

use Ornament\Storage;

class MyCustomModel
{
    use Storage;

    public function __construct($endpoint, $fields)
    {
        $adapter = new MyAwesomeApiAdapter($endpoint, $fields);
        $this->addAdapter($adapter, $endpoint, $fields);
    }
}
```

Of course, you could rewrite this so that `$fields` or perhaps even `$endpoint`
are guesstimated from the class signature (like in the PDO adapter), or the
adapter is injected and parts of the endpoint (scheme, domain) are already
pre-filled.

## Feel free to contribute!
Did you write a generic adapter for some well known service (we're personally
currently working on Twitter and Facebook default API adapters, for instance)?
Make it into a composer module and stick it on Packagist.org!

