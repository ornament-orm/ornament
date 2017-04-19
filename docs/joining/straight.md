# Getting data from more than one source
Apart from the simplest of projects, you'll usually want to mix and match models
to create a compound object. For instance, imagine a `User` model which looks to
a `user` table _and_ needs to left join a `session` table to see if that user is
also online at the moment, or an `Item` model which has a number of `images`
attached, one of which is the user's thumbnail.

## Straight joins
Let's start with the first example; the user with her online check. Our table
structure could look something like this:

```sql
CREATE TABLE people (
    id INTEGER PRIMARY KEY NOT NULL,
    -- other fields...
);

CREATE TABLE sess (
    -- assuming the id is PHP's session_id()
    id VARCHAR(32) PRIMARY KEY NOT NULL,
    -- other fields, e.g. actual session data...
    person INTEGER
);
```

What we'd like to do here is a query of the following sort:

```sql
SELECT people.*, sess.id sessid FROM people
    LEFT JOIN sess ON person = people.id;
```

Let's write a simple model for that which shows how we can instruct Ornament to
load data that way:

```php
<?php

/** @Include sess = [person = id] */
class UserModel
{
    use Ornament\Model;

    public $id;
    /** @From session.id */
    public $sessid;

    // etc.
}
```

We annotate the Model to `@Include` the `session` table. The exact semantics for
`@Include` will differ per adapter; the `Pdo` adapter uses the above form to
generate a `LEFT JOIN sess ON sess.person = people.id`.

> To generate a straight `JOIN` use `@Require` instead of `@Include`.

You'll have noticed that the concept of "joining" is pretty specific to certain
storage engines. E.g. an API probably won't support that. That is fine;
implementing adapters should use their own logic to accomplish the same
behaviour. E.g. an adapter for an API could issue multiple calls and
programmatically merge the results.

## Passing parameters
Sometimes you need to join data from multiple sources where not all parameters
can be inferred. E.g., in the preceding example we might only want `$sessid` to
be included if the _currently logged in user_ matches. SQL-wise that would mean
a query like the following:

```sql
-- If the currently logged in user has id "1":
SELECT people.*, sess.id sessid FROM people LEFT JOIN sess ON person = '1'
```

In other words, we need to programmatically inject a value into our adapter.
This is possible via the `setAdditionalQueryParameters` method on adapters,
combined with using `:paramName` placeholders in the `@Include` definition
instead of a mapped field name:

```php
<?php

/** @Include session = [user = :userid] */
class UserModel
{
    use Ornament\Model;

    public $id;
    /** @From session.id */
    public $sessid;

    // etc.

    public function __construct()
    {
        // etc.
        $this->addAdapter(new Ornament\Adapter\Pdo($myPdoObject))
            ->setAdditionalQueryParameters(compact('userid'));
    }
}
```

Non-`PDO` adapters should do their own search and replace (for `PDO` adapters the
`":name"` marks simply bind values to a prepared statement).

