As a tutorial, let's write a simple Twitter client using Ornament models. Our
client will:

- grab tweets from the actual Twitter API, and store them locally in a MySQL
  database;
- allow us to attach notes to a tweet

(We're choosing to offer this "functionality" to be able to demonstrate the
concept of multiple model sources. A real-life client would obviously allow you
to actually interact with Twitter's API, but that's beyong the scope of this
article.)

First, we're going to need a Tweet model to represent a single entity. Since we
will be using both an external Json datasource as well as a local MySQL
database, we need the model to recognize them:

> Note: a best practice is to dependency inject `PDO` and other sources into
> your code. This is not technically required by `Ornament`, but trust us:
> it will make your life easier. You can use your favourite way of doing this;
> in the remainder of this guide we'll use
> [`Disclosure`](http://github.com/monomelodies/disclosure) for that purpose.

    <?php

    use Ornament\Model;
    use PDO;
    use Ornament\Datasource\Json;
    use Disclosure\Injector;

    Model::inject(function (&$db) {
        $db = new PDO('dsn', 'user', 'pass');
    });
    Tweet::inject(function (&$api) {
        $headers = []; // Set these to your OAuth headers.
        $api = new Json(
            sprintf(
                'https://api.twitter.com/1.1/statuses/user_timeline.json?%s',
                'screen_name=yourname'
            ),
            $headers
        );
    });

    class Tweet extends Model
    {
        public function __construct()
        {
            $this->inject(function(PDO $db, Json $api) {});
            $this->addSource($db, function($id_str, $text, $created_at) {
            });
            $this->addSource
        }
    }


