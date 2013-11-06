<?php namespace Granada;

use ArrayAccess, Countable, IteratorAggregate, ArrayIterator;

/**
 * A result set class for working with collections of model instances
 * @author Simon Holywell <treffynnon@php.net>
 */
class ResultSet implements ArrayAccess, Countable, IteratorAggregate {
    /**
     * The current result set as an array
     * @var array
     */
    protected $_results = array();

    /**
     * Optionally set the contents of the result set by passing in array
     * @param array $results
     */
    public function __construct(array $results = array()) {
        $this->set_results($results);
    }

    /**
     * Set the contents of the result set by passing in array
     * @param array $results
     */
    public function set_results(array $results) {
        $this->_results = $results;
    }

    /**
     * Get the current result set as an array
     * @return array
     */
    public function get_results() {
        return $this->_results;
    }

    /**
     * Get the current result set as an array
     * @return array
     */
    public function as_array() {
        return $this->get_results();
    }

    /**
     * Get the current result set as an array
     * @return array
     */
    public function as_json() {
        $result = array();
        foreach($this->_results as $key=>$value){
            $result[] = $value->as_array();
        }
        return json_encode($result);
    }

    /**
     * Get the array keys (primary keys of the results)
     * @return array
     */
    public function keys(){
        return array_keys($this->_results);
    }

    /**
     * Merge the resultSet with an array
     * @return array
     */
    public function merge(IdiormResultSet $result) {
        array_push($this->_results, $this->_results);
        return $this;
    }

    /**
     * Get the first element of the result set
     * @return Model
     */
    public function first(){
        return reset($this->_results);
    }

    /**
     * Get the last element of the result set
     * @return Model
     */
    public function last(){
        return end($this->_results);
    }

    /**
     * Push an element on the result set
     * @return Model
     */
    public function add($value){
        array_push($this->_results, $value);
        return $this;
    }

    public function rewind() { return reset($this->_results); }
    public function current() { return current($this->_results); }
    public function key() { return key($this->_results); }
    public function next() { return next($this->_results); }
    public function valid() { return isset($this->_results[$this->id()]); }

    /**
     * Get the number of records in the result set
     * @return int
     */
    public function count() {
        return count($this->_results);
    }

    /**
     * Get an iterator for this object. In this case it supports foreaching
     * over the result set.
     * @return \ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator($this->_results);
    }

    /**
     * ArrayAccess
     * @param int|string $offset
     * @return bool
     */
    public function offsetExists($offset) {
        return isset($this->_results[$offset]);
    }

    /**
     * ArrayAccess
     * @param int|string $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        return $this->_results[$offset];
    }

    /**
     * ArrayAccess
     * @param int|string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) {
        if (is_null($offset))
        {
            $this->_results[] = $value;
        }
        else
        {
            $this->_results[$offset] = $value;
        }
    }

    /**
     * ArrayAccess
     * @param int|string $offset
     */
    public function offsetUnset($offset) {
        unset($this->_results[$offset]);
    }


    /**
     * Call a method on all models in a result set. This allows for method
     * chaining such as setting a property on all models in a result set or
     * any other batch operation across models.
     * @example ORM::for_table('Widget')->find_many()->set('field', 'value')->save();
     * @param string $method
     * @param array $params
     * @return \IdiormResultSet
     */
    public function __call($method, $params = array()) {
        foreach($this->_results as $model) {
            call_user_func_array(array($model, $method), $params);
        }
        return $this;
    }
}