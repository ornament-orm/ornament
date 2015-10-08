<?php

namespace Ornament;

class Model
{
    private $adapter;
    private $lastCheck = [];

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->lastCheck = $this->export();
    }

    /**
     * Query multiple models in an Adapter-independent way.
     *
     * @param object $parent The actual parent model to load into.
     * @param array $parameters Key/value pair of parameters to query on (e.g.,
     *                          ['parent' => 1]).
     * @param array $opts Optional hash of options.
     * @return array Array of models of the same type as $parent.
     */
    public function query($parent, array $parameters, array $opts = [])
    {
        return $this->adapter->query($parent, $parameters, $opts);
    }

    /**
     * (Re)load the model based on existing settings.
     */
    public function load()
    {
        $this->adapter->load($this);
    }

    /**
     * Persist this model back to whatever storage Adapter it was constructed
     * with.
     *
     * @return boolean true on success, false on error.
     */
    public function save()
    {
        if ($this->isNew()) {
            return $this->adapter->create($this);
        } else {
            return $this->adapter->update($this);
        }
    }

    /**
     * Delete this model from whatever storage Adapter it was constructed with.
     *
     * @return boolean true on success, false on error.
     */
    public function delete()
    {
        return $this->adapter->delete($this);
    }

    /**
     * Internal helper method to export the model's public properties.
     *
     * @return array An array of key/value pairs.
     */
    private function export()
    {
        $o = $this;
        return call_user_func(function () use ($o) {
            return get_object_vars($o);
        });
    }

    /**
     * Marks this model as being "new" (i.e., save proxies to create, not
     * update).
     */
    public function markNew()
    {
        $this->lastCheck = [];
    }

    /**
     * Checks if this model is "new".
     *
     * @return boolean true if new, otherwise false.
     */
    public function isNew()
    {
        return $this->lastCheck == [];
    }

    /**
     * Checks if this model is "dirty" compared to the last known "clean"
     * state.
     *
     * @return boolean true if dirty, otherwise false.
     */
    public function isDirty()
    {
        return $this->lastCheck != $this->export();
    }

    /**
     * Marks this model as "clean", i.e. set clean state to current state,
     * no questions asked.
     *
     * @see Ornament\Storage::markClean
     */
    public function markClean()
    {
        $this->lastCheck = $this->export();
    }
}

