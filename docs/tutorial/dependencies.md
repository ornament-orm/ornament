As a tutorial, let's write a simple Twitter client using Ornament models. Our
client will:

- grab tweets from the actual Twitter API, and store them locally in a MySQL
  database;
- allow us to attach notes to a tweet

(We're choosing to offer this "functionality" to be able to demonstrate the
concept of multiple model sources. A real-life client would obviously allow you
to actually interact with Twitter's API, but that's beyong the scope of this
article.)

First off, let's set up our dependencies for injection.

> Note: a best practice is to dependency inject `PDO` and other sources into
> your code. This is not technically required by `Ornament`, but trust us:
> it will make your life easier. You can use your favourite way of doing this;
> in the remainder of this guide we'll use
> [`Disclosure`](http://github.com/monomelodies/disclosure) for that purpose.

In some central file (e.g. `./src/dependencies.php` that you'll include
everywhere), setup the following:

    <?php

    use Ornament\Model;
    use PDO;

    Model::inject(function (&$db) {
        $db = new PDO('dsn', 'user', 'pass');
    });

Since the `Ornament\Model` already uses `Disclosure` internally, we just inject
the `PDO` object for our database generically. Now _every_ model we build will
have access to a `$this->db` member.

Our `Tweet` model should also have access to the Twitter API, but we'll handle
that in its own class definition, since it only applies to the tweets, not the
notes we'll be adding later.

