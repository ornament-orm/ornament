[API Index](ApiIndex.md)


Ornament\Adapter\Pdo
---------------


**Class name**: Pdo

**Namespace**: Ornament\Adapter



**This class implements**: [Ornament\Adapter](Ornament-Adapter.md)



    

    





Properties
----------


**$adapter**





    private  $adapter






**$table**





    private  $table






**$fields**





    private  $fields






**$primaryKey**





    private  $primaryKey = array()






**$statements**





    private  $statements = array()






Methods
-------


public **__construct** (  $adapter, $table,  $fields )











**Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| $adapter | PDO |  |
| $table | mixed |  |
| $fields | array |  |

--

public **setPrimaryKey** ( $field )











**Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| $field | mixed |  |

--

public **query** ( $model,  $ps,  $opts,  $c )











**Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| $model | mixed |  |
| $ps | array |  |
| $opts | array |  |
| $c | array |  |

--

public **load** (  $model )











**Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| $model | [Ornament\Model](Ornament-Model.md) |  |

--

private **getStatement** ( $sql )











**Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| $sql | mixed |  |

--

public **create** (  $model )











**Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| $model | [Ornament\Model](Ornament-Model.md) |  |

--

public **update** (  $model )











**Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| $model | [Ornament\Model](Ornament-Model.md) |  |

--

public **delete** (  $model )











**Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| $model | [Ornament\Model](Ornament-Model.md) |  |

--

[API Index](ApiIndex.md)
