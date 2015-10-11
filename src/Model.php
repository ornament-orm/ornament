<?php

namespace Ornament;

use SplObjectStorage;
use ReflectionProperty;

trait Model
{
    use Annotate;
    use Identify;

    /**
     * Private storage of registered adapters for this model.
     * @Private
     */
    private $__adapters;
    /**
     * Private storage of model's current state.
     * @Private
     */
    private $__state = 'new';

    /**
     * Register the specified adapter for the given identifier and fields.
     *
     * Generic method to add an Ornament adapter. Specific implementations
     * should generally supply a trait with an addImplementationAdapter that
     * takes care of wrapping the adapter in an Adapter-compatible object.
     *
     * Note that a model is considered "new" if fields are already populated.
     * This works for Pdo-style adapters, since PDO::FETCH_CLASS sets values
     * _prior_ to object instantiation. For adapters using other data sources
     * (e.g. an API) you would need to correct this manually.
     *
     * @param Ornament\Adapter $adapter Adapter object implementing the
     *  Ornament\Adapter interface.
     * @param string $id Identifier for this adapter (table name, API endpoint,
     *  etc.)
     * @param array $fields Array of fields (properties) this adapter works on.
     *  Should default to "all known public non-virtual members".
     * @return Ornament\Adapter The registered adapter, for easy chaining.
     */
    protected function addAdapter(Adapter $adapter, $id = null, array $fields = null)
    {
        if (!isset($this->__adapters)) {
            $this->__adapters = new SplObjectStorage;
        }
        if (!isset($id)) {
            $annotations = $this->annotations()['class'];
            $id = isset($annotations['Identifier']) ?
                $annotations['Identifier'] :
                $this->guessIdentifier();
        }
        $annotations = $this->annotations();
        if (!isset($fields)) {
            $fields = [];
            foreach ($annotations['properties'] as $prop => $anno) {
                if ($prop{0} != '_'
                    && !isset($anno['Virtual'])
                    && !isset($anno['Private'])
                    && !is_array($this->$prop)
                ) {
                    $fields[] = $prop;
                }
            }
        }
        $pk = [];
        foreach ($annotations as $prop => $anno) {
            if (isset($anno['PrimaryKey'])) {
                $pk[] = $prop;
            }
        }
        if (!$pk && in_array('id', $fields)) {
            $pk[] = 'id';
        }
        if ($pk) {
            call_user_func_array([$adapter, 'setPrimaryKey'], $pk);
        }
        foreach ($this->annotations()['properties'] as $prop => $annotations) {
            if (isset($annotations['Bitflag'])) {
                $this->$prop = new Bitflag(
                    $this->$prop,
                    $annotations['Bitflag']
                );
            }
        }
        $adapter->setIdentifier($id)->setFields($fields);
        $model = new Container($adapter);
        $new = true;
        foreach ($fields as $field => $alias) {
            $fname = is_numeric($field) ? $alias : $field;
            if (!(new ReflectionProperty($this, $fname))->isDefault()) {
                $new = false;
            }
            $model->$alias =& $this->$fname;
        }
        if ($new) {
            $model->markNew();
        } else {
            $model->markClean();
        }
        $this->__adapters->attach($model);
        foreach ($this->__adapters as $model) {
            if (!$model->isNew()) {
                $this->__state = 'clean';
            }
        }
        return $adapter;
    }

    /**
     * (Re)loads the current model based on the specified adapters.
     * Optionally also calls methods annotated with `onLoad`.
     *
     * @param bool $includeBase If set to true, loads the base model; if false,
     *                          only (re)loads linked models. Defaults to true.
     * @return void
     */
    public function load($includeBase = true)
    {
        $annotations = $this->annotations();
        if ($includeBase) {
            $errors = [];
            foreach ($this->__adapters as $model) {
                $model->load();
            }
        }
        foreach ($annotations['methods'] as $method => $anns) {
            if (isset($anns['onLoad']) && $anns['onLoad']) {
                $this->$method($annotations['properties']);
            }
        }
    }
    
    /**
     * Persists the model back to storage based on the specified adapters.
     * If an adapter supports transactions, you are encouraged to use them;
     * but you should do so in your own code.
     *
     * @return null|array null on success, or an array of errors encountered.
     * @throws Ornament\Exception\Immutable if the model implements the
     *  Immutable interface and is thus immutable.
     * @throws Ornament\Exception\Uncreateable if the model is new and implemnts
     *  the Uncreatable interface and can therefor not be created
     *  programmatically.
     */
    public function save()
    {
        if ($this instanceof Immutable) {
            throw new Exception\Immutable($this);
        }
        $errors = [];
        if (method_exists($this, 'notify')) {
            $notify = clone $this;
        }
        foreach ($this->__adapters as $model) {
            if ($model->isDirty()) {
                if ($model->isNew() && $this instanceof Uncreateable) {
                    throw new Exception\Uncreateable($this);
                }
                if (!$model->save()) {
                    $errors[] = true;
                }
            }
        }
        $annotations = $this->annotations()['properties'];
        foreach ($annotations as $prop => $anns) {
            if (isset($anns['Private']) || $prop{0} == '_') {
                continue;
            }
            $value = $this->$prop;
            if (is_array($value)) {
                $value = $this->$prop = new Collection($value);
            }
            if (is_object($value) && $value instanceof Collection) {
                $anns = $annotations[$prop];
                foreach ($this->$prop as $index => $model) {
                    if (Helper::isModel($model)) {
                        if (isset($anns['Mapping'])) {
                            $maps = $anns['Mapping'];
                        } else {
                            $maps = ['id' => $property];
                        }
                        foreach ($maps as $field => $mapto) {
                            $model->$field = $this->$mapto;
                        }
                        $model->__index($index);
                        if (!method_exists($model, 'isDirty')
                            || $model->isDirty()
                        ) {
                            if (!$model->save()) {
                                $errors[] = true;
                            }
                        }
                    }
                }
            }
            if (Helper::isModel($value)) {
                if (!method_exists($value, 'isDirty') || $value->isDirty()) {
                    if (!$value->save()) {
                        $errors[] = true;
                    }
                }
            }
        }
        if (isset($notify)) {
            $notify->notify();
        }
        $this->markClean();
        return $errors ? $errors : null;
    }

    /**
     * Deletes the current model from storage based on the specified adapters.
     * If an adapter supports transactions, you are encouraged to use them;
     * but you should do so in your own code.
     *
     * @return null|array null on success, or an array of errors encountered.
     * @throw Ornament\Exception\Undeleteable if the model implements the
     *  Undeleteable interface and is hence "protected".
     */
    public function delete()
    {
        if (method_exists($this, 'notify')) {
            $notify = clone $this;
        }
        if ($this instanceof Undeleteable) {
            throw new Exception\Undeleteable($this);
        }
        $errors = [];
        foreach ($this->__adapters as $adapter) {
            if ($error = $adapter->delete($this)) {
                $errors[] = $error;
            } else {
                $adapter->markDeleted();
            }
        }
        if (isset($notify)) {
            $notify->notify();
        }
        $this->__state = 'deleted';
        return $errors ? $errors : null;
    }

    /**
     * Get the current state of the model (new, clean, dirty or deleted).
     *
     * @return string The current state.
     */
    public function state()
    {
        // Do just-in-time checking for clean/dirty:
        if ($this->__state == 'clean') {
            foreach ($this->__adapters as $model) {
                if ($model->isDirty()) {
                    $this->__state = 'dirty';
                    break;
                }
            }
        }
        return $this->__state;
    }

    /**
     * Mark the current model as 'clean', i.e. not dirty. Useful if you manually
     * set values after loading from storage that shouldn't count towards
     * "dirtiness". Called automatically after saving.
     *
     * @return void
     */
    public function markClean()
    {
        foreach ($this->__adapters as $model) {
            $model->markClean();
        }
        $annotations = $this->annotations()['properties'];
        foreach ($annotations as $prop => $anns) {
            if (isset($anns['Private']) || $prop{0} == '_') {
                continue;
            }
            $value = $this->$prop;
            if (is_object($value) && Helper::isModel($value)) {
                if (method_exists($value, 'markClean')) {
                    $value->markClean();
                }
            } elseif (is_array($value)) {
                foreach ($this->$prop as $index => $model) {
                    if (is_object($model) && Helper::isModel($model)) {
                        if (method_exists($model, 'markClean')) {
                            $model->markClean();
                        }
                    }
                }
            }
        }
        $this->__state = 'clean';
    }

    /**
     * You'll want to specify a custom implementation for this. For models in an
     * array (on another model, of course) it is called with the current index.
     * Obviously, overriding is only needed if the index is relevant.
     *
     * @param integer $index The current index in the array.
     * @return void
     */
    public function __index($index)
    {
    }
}

