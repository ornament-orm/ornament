<?php

namespace Ornament;

use PDO as Base;

trait Pdo
{
    use Storage {
        Storage::addAdapter as addPdoAdapter;
    }

    public function addAdapter(Base $pdo)
    {
        $adapter = new Adapter\Pdo($pdo);
        return $this->addPdoAdapter($adapter);
    }
}

