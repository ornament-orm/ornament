[API Index](ApiIndex.md)


Ornament\Helper
---------------


**Class name**: Helper

**Namespace**: Ornament

**This is an abstract class**






    An abstract helper class containing some static methods use here and there
(mostly internally).

    







Methods
-------


public **normalize** ( string $input )


Normalize the inputted string for use in Ornament models.

A normalized string is a classname mapped to a table name, or a virtual
property named mapped to an &quot;actual&quot; property name. E.g.
`My\Awesome\Table` becomes `my_awesome_table`, and `someVirtualField`
becomes `some_virtual_field`.



This method is **static**.



**Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| $input | string | <p>The string to normalized.</p> |

--

public **denormalize** ( string $input )


The inverse of `Helper::normalize`. Note that this assumes the underscore
implies camelCase; we have no way of knowing if namespaces were intended
instead, or if the original class used underscores plus Capitals.

This is mostly used to denormalize virtual properties where this isn&#039;t
an issue anyway.



This method is **static**.



**Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| $input | string | <p>The normalize input to denormalize.</p> |

--

public **export** ( $object )


Exports the object as an array of public key/value pairs. Basically a
simple wrapper for `get_object_vars` but usefull when calling from a
`$this` context.





This method is **static**.



**Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| $object | mixed |  |

--

public **isModel** ( Object $object )


Returns true if the input is an Ornament-compatible model, false
otherwise.

This function _also_ returns true if the object has a `save` method. It&#039;s
then assumed that its implementation is compatible with Ornament&#039;s.

todo: Add aliases for other ORMs so the user can mix and match.



This method is **static**.



**Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| $object | Object | <p>The object to check.</p> |

--

[API Index](ApiIndex.md)
