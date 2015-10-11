<?php

class ObserverModel implements SplObserver
{
    use Ornament\Model;
    use Ornament\Observer;

    public $id;
    public $name;
    public $comment;
    
    public function __construct()
    {
        global $adapter;
        $this->addAdapter($adapter, 'dummy', ['id', 'name', 'comment']);
    }

    public function calledFromNotify(SubjectModel $subject)
    {
        echo 'yes';
    }

    public function alsoCalledFromNotify(SubjectModel $subject)
    {
        echo 'also';
    }

    public function neverCalled(DummyModel $subject)
    {
        echo 'noop';
    }

    /** @Blind */
    public function neverCalledForAnnotations(SubjectModel $subject)
    {
        echo 'foo';
    }

    /** @NotifyForState dirty */
    public function onlyWhenDirty(SubjectModel $subject)
    {
        echo 'dirty';
    }
}

class SubjectModel implements SplSubject
{
    use Ornament\Model;
    use Ornament\Subject;

    public $id;
    public $name;
    public $comment;
    
    public function __construct()
    {
        global $adapter;
        $this->addAdapter($adapter, 'dummy', ['id', 'name', 'comment']);
    }
}

class ObserverTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers Ornament\Observer
     * @covers Ornament\Subject
     */
    public function testObserving()
    {
        $observer = new ObserverModel;
        $subject = new SubjectModel;
        $subject->attach($observer);
        $subject->save();
        $another = new SubjectModel;
        $another->attach($observer);
        // Don't call save, nothing should happen...
        $this->expectOutputString('yesalso');
    }

    public function testDirty()
    {
        $observer = new ObserverModel;
        $subject = new SubjectModel;
        $subject->attach($observer);
        $subject->save();
        $subject->name = 'whatever';
        $subject->save();
        $this->expectOutputString('yesalsoyesalsodirty');
    }
}

