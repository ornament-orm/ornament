# Ornament
PHP5 ORM toolkit

ORM is a fickle beast. Many libraries (e.g. Propel, Doctrine, Eloquent etc)
assume your database should correspond to your models. This is simply not the
case; models contain business logic and may, may not or may in part refer to
database tables, NoSQL databases, flat files, an external API or whatever. The
point is: the models shouldn't care, and there should be no "conventional"
mapping through their names. (A common example would be a model of pages in
multiple languages, where the data might be stored in a `page` table and a
`page_i18n` table for the language-specific data.)

Also, the use of extensive and/or complicated config files sucks. (XML? This
is 2015, people!)

## Installation

### Composer (recommended)
Add "monomelodies/ornament" to your `composer.json` requirements:

```bash
$ composer require monomelodies/ornament
```

### Manual installation
1. Get the code;
    1. Clone the repository, e.g. from GitHub;
    2. Download the ZIP (e.g. from Github) and extract.
2. Make your project recognize Ornament:
    1. Register `/path/to/ornament/src` for the namespace `Ornament\\` in your
       PSR-4 autoloader (recommended);
    2. Alternatively, manually `include` the files you need.

## Basic usage
Ornament models (or "entities" if you're used to Doctrine-speak) are really
nothing more than vanilla PHP classes; there is no need to extend any base
object of sorts (since you might want to do that in your own framework!).

Ornament is a _toolkit_, so it supplies a number of `Trait`s one can `use` to
extend your models' behaviour beyond the ordinary.

