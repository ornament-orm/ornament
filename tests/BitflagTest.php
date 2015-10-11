<?php

class BitflagModel implements JsonSerializable
{
    use Ornament\JsonModel;

    /** @Private */
    private $pdo;

    /**
     * @PrimaryKey
     * @Bitflag nice = 1, cats = 2, code = 4
     */
    public $status;

    public function __construct()
    {
        $this->pdo = $GLOBALS['pdo'];
        $this->addAdapter(new Ornament\Adapter\Pdo($this->pdo));
    }
}

class BitflagTest extends PHPUnit_Extensions_Database_TestCase
{
    private static $pdo;
    private $conn;

    /**
     * @covers Ornament\Bitflag
     */
    public function testBitflags()
    {
        $model = new BitflagModel(self::$pdo);
        $this->assertInstanceOf('Ornament\Bitflag', $model->status);
        $model->status->code = true;
        $model->status->cats = true;
        $this->assertEquals(6, "{$model->status}");
        $model->status->code = false;
        $model->status->nice = true;
        $this->assertEquals(3, "{$model->status}");
        $exported = $model->jsonSerialize();
        $this->assertFalse($exported->status->code);
        $this->assertTrue($exported->status->cats);
        $this->assertTrue($exported->status->nice);
    }

    public function getConnection()
    {
        if ($this->conn === null) {
            if (!isset(self::$pdo)) {
                self::$pdo = new PDO('sqlite::memory:');
                self::$pdo->exec(file_get_contents(__DIR__.'/schema.sql'));
                $GLOBALS['pdo'] = self::$pdo;
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

