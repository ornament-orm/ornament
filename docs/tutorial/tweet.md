Next, let's define the `Tweet` model and specify exactly _how_ `Ornament` should
treat properties we are importing:

    use Ornament\Model;
    use Ornament\Source\Json;
    use PDO;

    class Tweet extends Model
    {
        protected $db;
        protected $api;

        public function __construct(array $data = [])
        {
            parent::__construct($data);
            $this->inject(function (PDO $db, Json $api) {});
            $this->addSource($this->db, function ($id_str, $text, $created_at) {
            });
            $this->addSource($this->api, function ($id_str, $text, $created_at) {
            });
        }
    }

Don't worry about the `$data` array in the constructor just yet. After our class
definition, we'll inject our Twitter API dependency:

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

Let's break down how that definition works. As you can see, we place two calls
to `Ornament`'s `source` method. We'll start with the `PDO` mapping, since
that's the most generic one:

    $this->source($this->db);

This adds the database as a "source" for the model. 
         ->map('id', 'text', function ($created_at) {
            return ['datecreated'];
         })
         ->query(function () {
            return new Ornament\Where('*');
         });


