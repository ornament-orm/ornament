<?php

namespace Ornament;

use PDO as Base;

trait Pdo
{
    use Storage {
        Storage::addAdapter as addPdoAdapter;
    }
    use Table;

    public function addAdapter(Base $pdo)
    {
        $adapter = new Adapter\Pdo($pdo);
        $adapter->setTable($this->guessTableName());
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
        return $this->addPdoAdapter($adapter);
    }
}

