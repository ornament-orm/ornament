# Overview of concepts and functionalities

## What is a model?
In MVC theory, a "model" is an entity (usually an object) that is concerned with
data and the business rules relevant for that data. That is to say, if you have
a model object representing e.g. a webshop transaction it could work as follows:

```php
<?php

$transaction = new TransactionModel;
$transaction->addProduct(new Product($product_id_1));
$transaction->addProduct(new Product($product_id_2));
// ...etc.

$transaction->complete();
```

The call to `TransactionModel::complete` would then persist the transaction to
somewhere (typically this would be an SQL database in this case).

The idea here is that wherever `complete` gets called (a controller, an API
callback, a shell script, ...) the calling code needs not to concern itself
with the exact inner workings. If ever the storage platform changes (say a
different database vendor, or even an external API) you'll only need to update
the `complete` method. In real world code, you would probably abstract this
away even further by moving the `addProduct` calls into some other method
(`initTransactionFromSession` for instance).

The idea of moving as much logic as you can into your models (as opposed to your
controllers, which is what many ORM-like frameworks encourage) is called
*fat models, skinny controllers*. A controller is just the glue and should
contain as little logic as possible.

## Object-relational mappers
An object-relational mapper (ORM) is a type of model that traditionally
corresponds to one row in a certain database table. This paradigm makes a
number of (confining) assumptions:

1. Your data is stored in a relational database;
2. Each row in a table can be represented by exactly one model, and vice versa;
3. Often, the underlying SQL is also abstracted away as much as possible, e.g.
   a `SomeThingModel` corresponds to the table `some_thing`.

In the real world, all above assumptions (especially on larger projects) are
simply wrong:

1. Data is stored all over the place (SQL, NoSQL, external APIs etc.);
2. Larger datasets are spread among multiple tables;
3. Complex manually tweaked queries are needed.

## Hello Ornament!
Ornament is an ORM toolkit that allows you to "decorate" your models (hence the
name) with common functionality, while at the same time not forcing you to
abandon your project's current "base model" (in case you were using one, but
it's quite common). What you end up storing where is not Ornament's concern.

