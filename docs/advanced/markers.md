# Marker interfaces
Ornament supplies a few marker interfaces you can use to quickly specify your
models' behaviours.

## Immutable
Models implementing the `Immutable` interface cannot be modified. Any attempt to
save an `Immutable` model will throw an `Ornament\Exception\Immutable`
exception.

## Uncreateable
Models implementing the `Uncreateable` interface cannot be created
programmatically. Any attempt to save an `Uncreateable` model that was marked as
"new" will throw an `Ornament\Exception\Uncreateable` exception.

## Undeleteable
Similarly, models implementing the `Undeleteable` interface are protected from
deletion. Any attempt to delete an `Undeleteable` model will throw an
`Ornament\Exception\Undeleteable` exception.

