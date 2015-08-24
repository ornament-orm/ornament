<?php

namespace Ornament;

use PDO as Base;

trait Pdo
{
    use Storage;
    use Table;

    public function addPdoAdapter(Base $pdo, $id = null, array $fields = null)
    {
        if (!isset($id)) {
            $id = $this->guessTableName();
        }
        if (!isset($fields)) {
            $fields = [];
            foreach (Repository::getProperties($this) as $prop) {
                if (property_exists($this, $prop)) {
                    $fields[] = $prop;
                }
            }
        }
        $pk = false;
        if (in_array('id', $fields)) {
            $pk = true;
        }
        $adapter = new Adapter\Pdo($pdo, $id, $fields);
        if ($pk) {
            $adapter->setPrimaryKey('id');
        }
        return $this->addAdapter($adapter, $id, $fields);
    }
}

