# Getting data from more than one source
Apart from the simplest of projects, you'll usually want to mix and match models
to create compound object. For instance, imagine a `User` model which looks to
a `user` table _and_ needs to left join a `session` table to see if that user is
also online at the moment, or an `Item` model which has a number of `images`
attached, one of which is the user's thumbnail.

## Straight joins
Let's start with the first example; the user with her online check. Our table
structure could look something like this:

```sql
CREATE TABLE user (
    id INTEGER PRIMARY KEY NOT NULL,
    -- other fields...
);

CREATE TABLE session (
    -- assuming the id is PHP's session_id()
    id VARCHAR(32) PRIMARY KEY NOT NULL,
    -- other fields, e.g. actual session data...
    user INTEGER
);
```

What we'd like to do here is a query of the following sort:

```sql
SELECT u.*, s.id sessid FROM user u LEFT JOIN session s ON s.user = u.id
```

Let's write a simple model for that which shows how we can instruct Ornament to
load data that way:

```php
<?php

/** @Include session = [user = id] */
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
generate a `LEFT JOIN session ON session.user = user.id`.

> To generate a straight `JOIN` use `@Require` instead of `@Include`.

