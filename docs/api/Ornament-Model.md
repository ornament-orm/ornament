[API Index](ApiIndex.md)


Ornament\Model
---------------


**Class name**: Model

**Namespace**: Ornament







    

    





Properties
----------


**$adapter**





    private  $adapter






**$lastCheck**





    private  $lastCheck = array()






Methods
-------


public **__construct** (  $adapter )











**Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| $adapter | [Ornament\Adapter](Ornament-Adapter.md) |  |

--

public **query** ( object $parent, array $parameters, array $ctor )


Query multiple models in an Adapter-independent way.








**Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| $parent | object | <p>The actual parent model to load into.</p> |
| $parameters | array | <p>Key/value pair of parameters to query on (e.g.,
[&#039;parent&#039; =&gt; 1]).</p> |
| $ctor | array | <p>Optional array of constructor arguments.</p> |

--

public **load** (  )


(Re)load the model based on existing settings.








--

public **save** (  )


Persist this model back to whatever storage Adapter it was constructed
with.








--

public **delete** (  )


Delete this model from whatever storage Adapter it was constructed with.








--

private **export** (  )


Internal helper method to export the model&#039;s public properties.








--

public **markNew** (  )


Marks this model as being &quot;new&quot; (i.e., save proxies to create, not
update).








--

public **isNew** (  )


Checks if this model is &quot;new&quot;.








--

public **isDirty** (  )


Checks if this model is &quot;dirty&quot; compared to the last known &quot;clean&quot;
state.








--

public **markClean** (  )


Marks this model as &quot;clean&quot;, i.e. set clean state to current state,
no questions asked.








--

[API Index](ApiIndex.md)
