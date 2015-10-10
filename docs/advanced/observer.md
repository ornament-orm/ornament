# Observer pattern
The Observer pattern is highly useful to automatically notify other models if
the model they were attached to changes. Ornament supplies two traits to aid you
in implementing this quickly:

## Observers
The Observer is the model that gets automatically updated. For instance, let's
say our application allows `User`s to store `Message`s. For each `Message`
created, the `User` must be notified. In this case, the Observer is the `User`
model:

```php
<?php

use Ornament\Observer;

class User implements SplObserver
{
    use Observer;

    public function newMessage(Message $model)
    {
        // Notify the user of the modification to $message.
    }
}
```

## Subjects
In order for this to work, we must also define "subjects". 

```php
<?php

ujse Ornament\Subject;

class Message implements SplSubject
{
    use Subject;

    public function __construct()
    {
        // Skipping other stuff like addAdapter...
        // ...and assuming we get $user from somewhere and it contains the
        // message's owner...
        $this->attach($user);
    }
}
```

That's it! Any call to `save` or `delete` on an attached `Message` will now
notify the observing `User`.

## Gotchas
Internally, the `Observer` trait defines an `update` method that checks all
public methods on a model and calls it with the notifying object as an argument
if, and only if, an object of that class (well, actually `instanceof`) is
specified as its only argument with type hinting. This means that if for some
reason you have another method also taking only a compatible object as an
argument, it will also get called. In the rare cases you run into this, you may
annotate the offending method `/** @Blind */` and the `Observer` trait will
ignore it.

Also note that the observing model receives a _clone_ of the original model.
This is done because we only want to notify after a successful operation, but
the observing class should receive the original, unsaved model (so it can do
dirty/new checking etc. and react accordingly). Once an object is saved it's
marked as 'pristine' again, so that would never work.

Implementors are free to define multiple observing methods for a single class.
Additionally, you can annotate observing methods with `/** NotifyForState */
specifying one or more states (`new`, `clean`, `dirty` or `deleted`) the
notification should trigger for.

So the following could be a valid strategy if it makes sense in your code:

```php
/** @NotifyForState new */
public function newMessage(Message $model)
{
}

/** @NotifyForState deleted */
public function deletedMessage(Message $model)
{
}

/** @Blind */
public function someOtherMethod(Message $model)
{
    // Needed for other reasons...
}
```

