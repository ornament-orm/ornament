<?php

class MyTableModel
{
    use Ornament\Pdo;

    public $id;
    public $name;
    public $comment;

    public function __construct(PDO $pdo)
    {
        $this->addPdoAdapter($pdo);
    }
}

class LinkedTableModel
{
    use Ornament\Pdo;

    public $id;
    public $mytable;
    public $points;

    public function __construct(PDO $pdo)
    {
        $this->addPdoAdapter($pdo);
    }

    public function getPercentage()
    {
        return round(($this->points / 5) * 100);
    }
}

class BitflagModel
{
    use Ornament\Pdo;
    use Ornament\Bitflag;

    const STATUS_NICE = 1;
    const STATUS_CATS = 2;
    const STATUS_CODE = 4;

    public $status;

    public function __construct(PDO $pdo)
    {
        $this->addPdoAdapter($pdo);
        $this->addBitflag('nice', self::STATUS_NICE, 'status');
        $this->addBitflag('cats', self::STATUS_CATS, 'status');
        $this->addBitflag('code', self::STATUS_CODE, 'status');
    }
}

class PdoTest extends PHPUnit_Extensions_Database_TestCase
{
    private static $pdo;
    private $conn;

    /**
     * @covers Ornament\Storage::dirty
     */
    public function testModel()
    {
        $model = new MyTableModel(self::$pdo);
        $model->name = 'Marijn';
        $model->comment = 'Hi Ornament';
        $model->save();
        $stmt = self::$pdo->prepare("SELECT * FROM my_table");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $this->assertEquals(1, count($rows));
        $model->comment = 'Awesome';
        $model->save();
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals('Awesome', $row['comment']);
    }

    /**
     * @expectedException Ornament\Exception\UnknownVirtualProperty
     */
    public function testVirtuals()
    {
        $model = new MyTableModel(self::$pdo);
        $model->name = 'Marijn';
        $model->comment = 'Hi Ornament';
        $model->save();
        var_dump($model->id);
        $linked = new LinkedTableModel(self::$pdo);
        $linked->mytable = $model->id;
        $linked->points = 4;
        $linked->save();
        $this->assertEquals(80, $linked->percentage);
        $linked->percentage = 70;
    }

    public function testBitflags()
    {
        $model = new BitflagModel(self::$pdo);
        $model->code = true;
        $model->cats = true;
        $this->assertEquals(6, $model->status);
        $model->code = false;
        $model->nice = true;
        $this->assertEquals(3, $model->status);
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

