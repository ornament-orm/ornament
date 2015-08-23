<?php

namespace Ornament;

use PDO as Base;

trait Pdo
{
    use Storage {
        Storage::addAdapter as addPdoAdapter;
    }
    use Table;

    public function addAdapter(Base $pdo, $id = null)
    {
        $adapter = new Adapter\Pdo($pdo);
        if (!isset($id)) {
            $id = $this->guessTableName();
        }
        $adapter->setTable($id);
        $fields = Repository::getProperties($this);
        if (in_array('id', $fields)) {
            $adapter->setPrimaryKey('id');
            foreach ($fields as $key => $value) {
                if ($value == 'id') {
                    unset($fields[$key]);
                }
            }
        }
        call_user_func_array([$adapter, 'setFields'], $fields);
        return $this->addPdoAdapter($adapter, $id);
    }
}

