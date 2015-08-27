[API Index](ApiIndex.md)


Ornament\Collection
---------------


**Class name**: Collection

**Namespace**: Ornament


**Parent class**: ArrayObject





    

    





Properties
----------


**$original**





    private  $original = array()






**$map**





    private  $map = array()






Methods
-------


public **__construct** ( $input )











**Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| $input | mixed |  |

--

public **save** (  )


Save the collection. This creates new models, updates dirty models and
deletes removed models (since the last save). Note that this is not in a
transaction; the programmer must implement that for compatible adapters.








--

public **dirty** (  )


Check if the collection is &quot;dirty&quot;, i.e. its contents have changed since
the last save.








--

public **markClean** (  )


Mark the collection as &quot;clean&quot;, i.e. in pristine state.








--

[API Index](ApiIndex.md)
