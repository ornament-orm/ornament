# ornament
PHP5 ORM toolkit

ORM is a fickle beast. Many libraries (e.g. Propel, Doctrine, Eloquent etc)
assume your database should correspond to your models. This is simply not the
case; models contain business logic and may, may not or may in part refer to
database tables, NoSQL databases, flat files, an external API or whatever. The
point is: the models shouldn't care, and there should be no "conventional"
mapping through their names. (A common example would be a model of pages in
multiple languages, where the data might be stored in a `page` table and a
`page_i18n` table for the language-specific data.)

