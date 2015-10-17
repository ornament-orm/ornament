<?php

use Ornament\Adapter;

class JoinTest extends PHPUnit_Extensions_Database_TestCase
{
    private static $pdo;
    private $conn;

    public function testStraightJoin()
    {
        $model = new MyTableModel(self::$pdo);
        $model->name = 'Marijn';
        $model->comment = 'Hi Ornament';
        $model->save();
        $linked = new LinkedTableModel(self::$pdo);
        $linked->mytable = $model->id;
        $linked->points = 4;
        $linked->save();
        $id = $model->id;
        unset($model, $linked);
        $model = new StraightJoinModel(self::$pdo);
        $model->id = $id;
        $model->load();
        $this->assertEquals(4, $model->points);
        $this->assertEquals(1, count(StraightJoinModel::query([], [], [self::$pdo])));
    }

    public function testVirtualJoin()
    {
        $model = new MyTableModel(self::$pdo);
        $model->name = 'Marijn';
        $model->comment = 'Hi Ornament';
        $model->save();
        $linked = new LinkedTableModel(self::$pdo);
        $linked->mytable = $model->id;
        $linked->points = 4;
        $linked->save();
        $id = $linked->id;
        unset($model, $linked);
        $linked = new LinkedTableModel(self::$pdo);
        $linked->id = $id;
        $linked->load();
        $this->assertTrue($linked->mytable instanceof MyTableModel);
    }

    public function testMultipleJoin()
    {
        $models = MultiJoinModel::query([], [], [self::$pdo]);
        $this->assertEquals(1, count($models));
    }

    public function getConnection()
    {
        if ($this->conn === null) {
            if (!isset(self::$pdo)) {
                self::$pdo = new PDO('sqlite::memory:');
                self::$pdo->exec(file_get_contents(__DIR__.'/schema.sql'));
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, 'test');
        }
        return $this->conn;
    }
    
    public function getDataSet()
    {
        return $this->createXmlDataSet(__DIR__.'/dataset.xml');
    }
}

