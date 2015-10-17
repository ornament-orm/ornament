<?php

class MyTableModel
{
    use Ornament\Model;
    use Ornament\Query;
    
    /** @Private */
    private $pdo;
    
    public $id;
    public $name;
    public $comment;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->addAdapter(new Ornament\Adapter\Pdo($pdo));
    }
}

class LinkedTableModel
{
    use Ornament\Model;
    use Ornament\Autoload;
    use Ornament\Query;
    
    /** @Private */
    private $pdo;
    
    public $id;
    /**
    * @Model MyTableModel
    * @Constructor [ pdo ]
    */
    public $mytable;
    public $points;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->addAdapter(new Ornament\Adapter\Pdo($pdo));
    }
    
    public function getPercentage()
    {
        return round(($this->points / 5) * 100);
    }
    
    public function __index($index)
    {
    }
}

/**
 * @Identifier my_table
 * @Require linked_table = [mytable = id]
 */
class StraightJoinModel
{
    use Ornament\Model;
    use Ornament\Autoload;
    use Ornament\Query;

    /** @Private */
    private $pdo;

    public $id;
    public $name;
    public $comment;
    /** @From linked_table.points */
    public $points;

    public function __construct(PDO $pdo)
    {
        $this->addAdapter(new Ornament\Adapter\Pdo($pdo));
    }
}

/**
 * @Identifier multijoin
 * @Require my_table = [id = mytable]
 * @Include linked_table = [id = linkedtable]
 */
class MultiJoinModel
{
    use Ornament\Model;
    use Ornament\Autoload;
    use Ornament\Query;

    /** @Private */
    private $pdo;

    public $id;
    /** @From my_table.comment */
    public $comment;
    /** @From linked_table.points */
    public $points;

    public function __construct(PDO $pdo)
    {
        $this->addAdapter(new Ornament\Adapter\Pdo($pdo));
    }
}

