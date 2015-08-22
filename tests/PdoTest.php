<?php

class MyTableModel
{
    use Ornament\Pdo;

    public $id;
    public $name;
    public $comment;

    public function __construct(PDO $pdo)
    {
        $this->addAdapter($pdo);
    }
}

class PdoTest extends PHPUnit_Extensions_Database_TestCase
{
    private static $pdo;
    private $conn;

    public function testModel()
    {
        $model = new MyTableModel(self::$pdo);
        $model->name = 'Marijn';
        $model->comment = 'Hi Ornament';
        $this->assertTrue($model->dirty());
        $model->save();
        $this->assertFalse($model->dirty());
        $stmt = self::$pdo->prepare("SELECT * FROM my_table");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $this->assertEquals(1, count($rows));
        $model->comment = 'Awesome';
        $this->assertTrue($model->dirty());
        $model->save();
        $this->assertFalse($model->dirty());
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('Awesome', $row['comment']);
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

