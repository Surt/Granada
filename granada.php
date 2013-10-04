<?php

   /**
    *
    * Granada
    *
    * Copyright (c) 2013, Erik Wiesenthal
    * All rights reserved
    *
    * http://github.com/Surt/Granada/
    *
    * A simple Active Record implementation built on top of Idiorm, Paris and Eloquent
    * ( http://github.com/Surt/Granada/ ).
    *
    * You should include Idiorm before you include this file:
    * require_once 'your/path/to/idiorm.php';
    *
    * BSD Licensed.
    *
    * Copyright (c) 2010, Jamie Matthews
    * All rights reserved.
    *
    * Redistribution and use in source and binary forms, with or without
    * modification, are permitted provided that the following conditions are met:
    *
    * * Redistributions of source code must retain the above copyright notice, this
    * list of conditions and the following disclaimer.
    *
    * * Redistributions in binary form must reproduce the above copyright notice,
    * this list of conditions and the following disclaimer in the documentation
    * and/or other materials provided with the distribution.
    *
    * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
    * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
    * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
    * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE
    * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
    * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
    * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
    * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
    * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
    * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
    *
    */

    /**
     * Subclass of Idiorm's ORM class that supports
     * returning instances of a specified class rather
     * than raw instances of the ORM class.
     *
     * You shouldn't need to interact with this class
     * directly. It is used internally by the Model base
     * class.
     */
    class ORMWrapper extends ORM {

        /**
         * The wrapped find_one and find_many classes will
         * return an instance or instances of this class.
         */
        protected $_class_name;

        public $relationships = array();

        /**
         * Set the name of the class which the wrapped
         * methods should return instances of.
         */
        public function set_class_name($class_name) {
            $this->_class_name = $class_name;
        }

        /**
         * Add a custom filter to the method chain specified on the
         * model class. This allows custom queries to be added
         * to models. The filter should take an instance of the
         * ORM wrapper as its first argument and return an instance
         * of the ORM wrapper. Any arguments passed to this method
         * after the name of the filter will be passed to the called
         * filter function as arguments after the ORM class.
         */
        public function filter() {
            $args = func_get_args();
            $filter_function = array_shift($args);
            array_unshift($args, $this);
            if (method_exists($this->_class_name, $filter_function)) {
                return call_user_func_array(array($this->_class_name, $filter_function), $args);
            }
        }

        /**
         * Factory method, return an instance of this
         * class bound to the supplied table name.
         *
         * A repeat of content in parent::for_table, so that
         * created class is ORMWrapper, not ORM
         */
        public static function for_table($table_name, $connection_name = parent::DEFAULT_CONNECTION) {
            self::_setup_db($connection_name);
            return new self($table_name, array(), $connection_name);
        }

        /**
         * Method to create an instance of the model class
         * associated with this wrapper and populate
         * it with the supplied Idiorm instance.
         */
        protected function _create_model_instance($orm) {
            if ($orm === false) {
                return false;
            }
            $model = new $this->_class_name();
            $model->set_orm($orm);
            return $model;
        }

        /**
         *
         * Overload select_expr name
         *
         */
        public function select_raw($expr, $alias=null){
            return $this->select_expr($expr, $alias);
        }

        /**
         * Special method to query the table by its primary key
         */
        public function where_id_in($ids) {
            return $this->where_in($this->_get_id_column_name(), $ids);
        }

        /**
         *
         * Create raw_join
         *
         */
        public function raw_join($join){
            $this->_join_sources[] = "$join";
            return $this;
        }

        /**
         * Add an unquoted expression to the list of columns to GROUP BY
         */
        public function group_by_raw($expr) {
            $this->_group_by[] = $expr;
            return $this;
        }


        /**
         * Add an unquoted expression as an ORDER BY clause
         */
        public function order_by_raw($clause) {
            $this->_order_by[] = $clause;
            return $this;
        }

        /**
         *
         * To create and save multiple elements, easy way
         * Using an array with rows array(array('name'=>'value',...), array('name2'=>'value2',...),..)
         * or a array multiple
         *
         */
        public function insert($rows)
        {
            ORM::get_db()->beginTransaction();
            foreach ($rows as $row) {
                $class = $this->_class_name;
                $class::create($row)->save();
            }
            ORM::get_db()->commit();
            return ORM::get_db()->lastInsertId();
        }

        /**
         * Wrap Idiorm's find_one method to return
         * an instance of the class associated with
         * this wrapper instead of the raw ORM class.
         * Added: hidrate the model instance before returning
         */
        public function find_one($id=null) {
            $result = $this->_create_model_instance(parent::find_one($id));
            if($result){
                $results = array($result->{$this->_instance_id_column} => $result);
                Eager::hydrate($this, $results);
                $result = $results[$result->{$this->_instance_id_column}];
            }
            return $result;
        }

        /**
         * Override Idiorm _instances_with_id_as_key
         * Create instances of each row in the result and map
         * them to an associative array with the primary IDs as
         * the array keys.
         * Added: the array result key = primary key from the model
         * Added: Eager loading of relationships defined "with()"
         * @param array $rows
         * @return array
         */
        protected function _instances_with_id_as_key($rows){
            $size = count($rows);
            $instances = array();
            for ($i = 0; $i < $size; $i++) {
                $row = $this->_create_instance_from_row($rows[$i]);
                $row = $this->_create_model_instance($row);
                $key = (isset($row->{$this->_instance_id_column})) ? $row->id() : $i;
                $instances[$key] = $row;
            }

            return $instances ? Eager::hydrate($this, $instances) : $instances;
        }

        /**
         * Pluck a single column from the result.
         *
         * @param  string  $column
         * @return mixed
         */
        public function pluck($column)
        {
            $result = (array) $this->find_one();

            if(count($result) > 0) {
                $row = reset($result);
                return $row[$column];
            }
            else {
                return null;
            }
        }

        /**
         * Wrap Idiorm's create method to return an
         * empty instance of the class associated with
         * this wrapper instead of the raw ORM class.
         */
        public function create($data=null) {
            return $this->_create_model_instance(parent::create($data));
        }

        /**
         * Added: Set the eagerly loaded models on the queryable model.
         *
         * @return Model
         */
        public function with()
        {
            $this->relationships  = array_merge($this->relationships, func_get_args());
            return $this;
        }

        /**
         * Added: Reset relation deletes the relationship "where" condition.
         *
         * @return Model
         */
        public function reset_relation()
        {
            array_shift($this->_where_conditions);
            return $this;
        }

        /**
         * Added: Return pairs as result array('keyrecord_value'=>'valuerecord_value',.....)
         *
         */
        public function find_pairs($key = false, $value = false)
        {
            $key = ($key) ? $key : 'id';
            $value = ($value) ? $value : 'name';
            return self::assoc_to_keyval($this->select_raw("$key,$value")->order_by_asc($value)->find_array(), $key, $value);
        }


        /**
         * Converts a multi-dimensional associative array into an array of key => values with the provided field names
         *
         * @param   array   the array to convert
         * @param   string  the field name of the key field
         * @param   string  the field name of the value field
         * @return  array
         */
        public static function assoc_to_keyval($assoc = null, $key_field = null, $val_field = null)
        {
            if(empty($assoc) OR empty($key_field) OR empty($val_field))
            {
                return null;
            }

            $output = array();
            foreach($assoc as $row)
            {
                if(isset($row[$key_field]) AND isset($row[$val_field]))
                {
                    $output[$row[$key_field]] = $row[$val_field];
                }
            }

            return $output;
        }

        /**
         *
         * Overrides __call to check for filter_$method names defined
         * You can now define filters methods on the Granada Model as
         * public static function filter_{filtermethodname} and call it from a static call
         * ModelName::filtermethodname->......
         *
         */
        public function __call($method, $parameters){
            if(method_exists($this->_class_name,'filter_'.$method)){
                array_unshift($parameters, $this);
                return call_user_func_array(array($this->_class_name,'filter_'.$method), $parameters);
            }
            else {
                throw new Exception(" no static $method found or static method 'filter_$method' not defined in ".$this->_class_name);
            }
        }
    }

    /**
     * Model base class. Your model objects should extend
     * this class. A minimal subclass would look like:
     *
     * class Widget extends Model {
     * }
     *
     */
    class Model implements ArrayAccess  {

        // Default ID column for all models. Can be overridden by adding
        // a public static _id_column property to your model classes.
        const DEFAULT_ID_COLUMN = 'id';

        // Default foreign key suffix used by relationship methods
        const DEFAULT_FOREIGN_KEY_SUFFIX = '_id';

        /**
         * Set a prefix for model names. This can be a namespace or any other
         * abitrary prefix such as the PEAR naming convention.
         * @example Model::$auto_prefix_models = 'MyProject_MyModels_'; //PEAR
         * @example Model::$auto_prefix_models = '\MyProject\MyModels\'; //Namespaces
         * @var string
         */
        public static $auto_prefix_models = null;

        /**
         * The ORM instance used by this model
         * instance to communicate with the database.
         */
        public $orm;

        /**
         * The model's $relationships attributes.
         *
         * $relationships attributes will not be saved to the database, and are
         * primarily used to hold relationships.
         * __set and __get need the relationship method defined on the model to determine if the relationship exists.
         *
         * @var array
         */
        public $relationships = array();

        /**
         * The relationship type the model is currently resolving.
         *
         * @var string
         */
        public $relating;

        /**
         * The foreign key of the "relating" relationship.
         *
         * @var string
         */
        public $relating_key;

        /**
         * The table name of the model being resolved.
         *
         * This is used during has_many_through eager loading.
         *
         * @var string
         */
        public $relating_table;


        /**
         * Retrieve the value of a static property on a class. If the
         * class or the property does not exist, returns the default
         * value supplied as the third argument (which defaults to null).
         */
        protected static function _get_static_property($class_name, $property, $default=null) {
            if (!class_exists($class_name) || !property_exists($class_name, $property)) {
                return $default;
            }
            $properties = get_class_vars($class_name);
            return $properties[$property];
        }

        /**
         * Static method to get a table name given a class name.
         * If the supplied class has a public static property
         * named $_table, the value of this property will be
         * returned. If not, the class name will be converted using
         * the _class_name_to_table_name method method.
         */
        protected static function _get_table_name($class_name) {
            $specified_table_name = self::_get_static_property($class_name, '_table');
            if (is_null($specified_table_name)) {
                return self::_class_name_to_table_name($class_name);
            }
            return $specified_table_name;
        }

        /**
         * Convert a namespace to the standard PEAR underscore format.
         *
         * Then convert a class name in CapWords to a table name in
         * lowercase_with_underscores.
         *
         * Finally strip doubled up underscores
         *
         * For example, CarTyre would be converted to car_tyre. And
         * Project\Models\CarTyre would be project_models_car_tyre.
         */
        protected static function _class_name_to_table_name($class_name) {
            return strtolower(preg_replace(
                array('/\\\\/', '/(?<=[a-z])([A-Z])/', '/__/'),
                array('_', '_$1', '_'),
                ltrim($class_name, '\\')
            ));
        }

        /**
         * Return the ID column name to use for this class. If it is
         * not set on the class, returns null.
         */
        protected static function _get_id_column_name($class_name) {
            return self::_get_static_property($class_name, '_id_column', self::DEFAULT_ID_COLUMN);
        }

        /**
         * Build a foreign key based on a table name. If the first argument
         * (the specified foreign key column name) is null, returns the second
         * argument (the name of the table) with the default foreign key column
         * suffix appended.
         */
        protected static function _build_foreign_key_name($specified_foreign_key_name, $table_name) {
            if (!is_null($specified_foreign_key_name)) {
                return $specified_foreign_key_name;
            }
            return $table_name . self::DEFAULT_FOREIGN_KEY_SUFFIX;
        }

        /**
         * Factory method used to acquire instances of the given class.
         * The class name should be supplied as a string, and the class
         * should already have been loaded by PHP (or a suitable autoloader
         * should exist). This method actually returns a wrapped ORM object
         * which allows a database query to be built. The wrapped ORM object is
         * responsible for returning instances of the correct class when
         * its find_one or find_many methods are called.
         */
        public static function factory($class_name, $connection_name = null) {
            $class_name = self::$auto_prefix_models . $class_name;
            $table_name = self::_get_table_name($class_name);

            if ($connection_name == null) {
               $connection_name = self::_get_static_property(
                   $class_name,
                   '_connection_name',
                   ORMWrapper::DEFAULT_CONNECTION
               );
            }
            $wrapper = ORMWrapper::for_table($table_name, $connection_name);
            $wrapper->set_class_name($class_name);
            $wrapper->use_id_column(self::_get_id_column_name($class_name));
            return $wrapper;
        }

        /**
         * Internal method to construct the queries for both the has_one and
         * has_many methods. These two types of association are identical; the
         * only difference is whether find_one or find_many is used to complete
         * the method chain.
         */
        protected function _has_one_or_many($associated_class_name, $foreign_key_name=null, $foreign_key_name_in_current_models_table=null, $connection_name=null) {
            $base_table_name = self::_get_table_name(get_class($this));
            $foreign_key_name = self::_build_foreign_key_name($foreign_key_name, $base_table_name);

            $where_value = ''; //Value of foreign_table.{$foreign_key_name} we're
                               //looking for. Where foreign_table is the actual
                               //database table in the associated model.

            if(is_null($foreign_key_name_in_current_models_table)) {
                //Match foreign_table.{$foreign_key_name} with the value of
                //{$this->_table}.{$this->id()}
                $where_value = $this->id();
            } else {
                //Match foreign_table.{$foreign_key_name} with the value of
                //{$this->_table}.{$foreign_key_name_in_current_models_table}
                $where_value = $this->$foreign_key_name_in_current_models_table;
            }

            // Added: to determine eager load relationship parameters
            $this->relating_key = $foreign_key_name;
            return self::factory($associated_class_name, $connection_name)->where($foreign_key_name, $where_value);
        }

        /**
         * Helper method to manage one-to-one relations where the foreign
         * key is on the associated table.
         */
        protected function has_one($associated_class_name, $foreign_key_name=null, $foreign_key_name_in_current_models_table=null, $connection_name=null) {
            // Added: to determine eager load relationship parameters
            $this->relating = 'has_one';
            return $this->_has_one_or_many($associated_class_name, $foreign_key_name, $foreign_key_name_in_current_models_table, $connection_name);
        }

        /**
         * Helper method to manage one-to-many relations where the foreign
         * key is on the associated table.
         */
        protected function has_many($associated_class_name, $foreign_key_name=null, $foreign_key_name_in_current_models_table=null, $connection_name=null) {
            // Added: to determine eager load relationship parameters
            $this->relating = 'has_many';
            return $this->_has_one_or_many($associated_class_name, $foreign_key_name, $foreign_key_name_in_current_models_table, $connection_name);
        }

        /**
         * Helper method to manage one-to-one and one-to-many relations where
         * the foreign key is on the base table.
         */
        protected function belongs_to($associated_class_name, $foreign_key_name=null, $foreign_key_name_in_associated_models_table=null, $connection_name=null) {
            // Added: to determine eager load relationship parameters
            $this->relating = 'belongs_to';

            $associated_table_name = self::_get_table_name(self::$auto_prefix_models . $associated_class_name);
            $foreign_key_name = self::_build_foreign_key_name($foreign_key_name, $associated_table_name);
            $associated_object_id = $this->$foreign_key_name;

            // Added: to determine eager load relationship parameters
            $this->relating_key = $foreign_key_name;

            $desired_record = null;

            if( is_null($foreign_key_name_in_associated_models_table) ) {
                //"{$associated_table_name}.primary_key = {$associated_object_id}"
                //NOTE: primary_key is a placeholder for the actual primary key column's name
                //in $associated_table_name
                $desired_record = self::factory($associated_class_name, $connection_name)->where_id_is($associated_object_id);
            } else {
                //"{$associated_table_name}.{$foreign_key_name_in_associated_models_table} = {$associated_object_id}"
                $desired_record = self::factory($associated_class_name, $connection_name)->where($foreign_key_name_in_associated_models_table, $associated_object_id);
            }

            return $desired_record;
        }

        /**
         * Helper method to manage many-to-many relationships via an intermediate model. See
         * README for a full explanation of the parameters.
         */
        protected function has_many_through($associated_class_name, $join_class_name=null, $key_to_base_table=null, $key_to_associated_table=null,  $key_in_base_table=null, $key_in_associated_table=null, $connection_name=null) {
            // Added: to determine eager load relationship parameters
            $this->relating = 'has_many_through';

            $base_class_name = get_class($this);

            // The class name of the join model, if not supplied, is
            // formed by concatenating the names of the base class
            // and the associated class, in alphabetical order.
            if (is_null($join_class_name)) {
                $model = explode('\\', $base_class_name);
                $model_name = end($model);
                if (substr($model_name, 0, strlen(self::$auto_prefix_models)) == self::$auto_prefix_models) {
                    $model_name = substr($model_name, strlen(self::$auto_prefix_models), strlen($model_name));
                }
                $class_names = array($model_name, $associated_class_name);
                sort($class_names, SORT_STRING);
                $join_class_name = join("", $class_names);
            }

            // Get table names for each class
            $base_table_name = self::_get_table_name($base_class_name);
            $associated_table_name = self::_get_table_name(self::$auto_prefix_models . $associated_class_name);
            $join_table_name = self::_get_table_name(self::$auto_prefix_models . $join_class_name);

            // Get ID column names
            $base_table_id_column = (is_null($key_in_base_table)) ?
                self::_get_id_column_name($base_class_name) :
                $key_in_base_table;
            $associated_table_id_column = (is_null($key_in_associated_table)) ?
                self::_get_id_column_name(self::$auto_prefix_models . $associated_class_name) :
                $key_in_associated_table;

            // Get the column names for each side of the join table
            $key_to_base_table = self::_build_foreign_key_name($key_to_base_table, $base_table_name);
            $key_to_associated_table = self::_build_foreign_key_name($key_to_associated_table, $associated_table_name);

            /*
                "   SELECT {$associated_table_name}.*
                      FROM {$associated_table_name} JOIN {$join_table_name}
                        ON {$associated_table_name}.{$associated_table_id_column} = {$join_table_name}.{$key_to_associated_table}
                     WHERE {$join_table_name}.{$key_to_base_table} = {$this->$base_table_id_column} ;"
            */

            // Added: to determine eager load relationship parameters
            $this->relating_key = array(
                $key_to_base_table,
                $key_to_associated_table
            );
            $this->relating_table = $join_table_name;

            return self::factory($associated_class_name, $connection_name)
                ->select("{$associated_table_name}.*")
                ->join($join_table_name, array("{$associated_table_name}.{$associated_table_id_column}", '=', "{$join_table_name}.{$key_to_associated_table}"))
                ->where("{$join_table_name}.{$key_to_base_table}", $this->$base_table_id_column); ;
        }

        /**
         * Set the wrapped ORM instance associated with this Model instance.
         */
        public function set_orm($orm) {
            $this->orm = $orm;
        }

        /**
         * Magic getter method, allows $model->property access to data.
         * Added: check for
         *      get_{property_name} method defined in model
         *      fetched relationships
         *      not loaded relationship to fetch it if method exists and "lazy loading" it
         */
        public function __get($property) {
            if($result = $this->orm->get($property))
            {
                return $result;
            }
            elseif(method_exists($this, $method = 'get_'.$property))
            {
                return $this->$method();
            }
            elseif(isset($this->relationships[$property]))
            {
                return $this->relationships[$property];
            }
            elseif(method_exists($this, $property))
            {
                if ($property != self::_get_id_column_name(get_class($this))) {
                    $relation = $this->$property();
                    return $this->relationships[$property] = (in_array($this->relating, array('has_one', 'belongs_to'))) ? $relation->find_one() : $relation->find_many();
                }
                else
                    return false;
            }
            else {
                return null;
            }
        }

        /**
         * Magic setter method, allows $model->property = 'value' access to data.
         * Added: use Model methods to determine if a relationship exists and populate it on $relationships instead of properties
         */
        public function __set($property, $value) {
            if(!is_array($property) && method_exists($this,$property)){
                $this->relationships[$property] = $value;
            }
            else {
                $this->set($property, $value);
            }
        }

        /**
         * Magic isset method, allows isset($model->property) to work correctly.
         */
        public function __isset($property) {
            return (isset($this->relationships[$property]) || $this->orm->__isset($property) || method_exists($this, $method = 'get_'.$property));
        }

        /**
         * Getter method, allows $model->get('property') access to data
         */
        public function get($property) {
            return $this->orm->get($property);
        }

        /**
         * Setter method, allows $model->set('property', 'value') access to data.
         * @param string|array $key
         * @param string|null $value
         */
        public function set($property, $value = null) {
            if (!is_array($property)) {
                $property = array($property => $value);
            }

            foreach ($property as $field => $val) {
                if(method_exists($this, $method = 'set_'.$field)){
                    $this->$method($val);
                }
            }
            return $this->orm->set($property, $value);
        }

        /**
         * Setter method, allows $model->set_expr('property', 'value') access to data.
         * @param string|array $key
         * @param string|null $value
         */
        public function set_expr($property, $value = null) {
            $this->orm->set_expr($property, $value);
        }

        /**
         * ArrayAccess
         * @param int|string $offset
         * @return bool
         */
        public function offsetExists($offset) {
            return $this->__isset($offset);
        }

        /**
         * ArrayAccess
         * @param int|string $offset
         * @return mixed
         */
        public function offsetGet($offset) {
             return $this->__get($offset);
        }

        /**
         * ArrayAccess
         * @param int|string $offset
         * @param mixed $value
         */
        public function offsetSet($offset, $value) {
            return $this->__set($offset, $value);
        }

        /**
         * ArrayAccess
         * @param int|string $offset
         */
        public function offsetUnset($offset) {
            $this->orm->offsetUnset($offset);
        }

        /**
         * Check whether the given field has changed since the object was created or saved
         */
        public function is_dirty($property) {
            return $this->orm->is_dirty($property);
        }

        /**
         * Check whether the model was the result of a call to create() or not
         * @return bool
         */
        public function is_new() {
            return $this->orm->is_new();
        }

        /**
         * Wrapper for Idiorm's as_array method.
         */
        public function as_array() {
            $args = func_get_args();
            return call_user_func_array(array($this->orm, 'as_array'), $args);
        }

        /**
         * Save the data associated with this model instance to the database.
         */
        public function save() {
            return $this->orm->save();
        }

        /**
         * Delete the database row associated with this model instance.
         */
        public function delete() {
            return $this->orm->delete();
        }

        /**
         * Get the database ID of this model instance.
         */
        public function id() {
            return $this->orm->id();
        }

        /**
         * Hydrate this model instance with an associative array of data.
         * WARNING: The keys in the array MUST match with columns in the
         * corresponding database table. If any keys are supplied which
         * do not match up with columns, the database will throw an error.
         */
        public function hydrate($data) {
            $this->orm->hydrate($data)->force_all_dirty();
        }

        /**
         * Calls static methods directly on the ORMWrapper
         *
         */
        public static function __callStatic($method, $parameters) {
            if(function_exists('get_called_class')) {
                $model = self::factory(get_called_class());
                return call_user_func_array(array($model, $method), $parameters);
            }
        }
    }


    /**
    * @author Erik Wiesenthal
    * @email erikwiesenthal@hotmail.com
    * @project Paris / Granada
    * @copyright 2012
    *
    * Mashed from eloquent https://github.com/taylorotwell/eloquent
    * to works with idiorm + http://github.com/j4mie/paris/
    *
    */

    class Eager
    {
        /**
         *
         * Attempts to execute any relationship defined for eager loading
         *
         */
        public static function hydrate($orm, &$results)
        {
            if (count($results) > 0)
            {
                foreach ($orm->relationships as $include)
                {
                    $relationship      = (is_array($include))?key($include):$include;
                    $relationship_args = array();
                    $relationship_with = false;

                   // check args and eager loading for relationships
                    if(is_array($include)){
                        if(isset($include[$relationship]['with'])){
                            $relationship_with = $include[$relationship]['with'];
                            unset($include[$relationship]['with']);    // don't add eager loading relationships for the eager load args
                        }
                        $relationship_args = $include[$relationship];
                    }

                    // check if relationship exists on the model
                    $model = ($results instanceof IdiormResultSet)?$results->first():$orm->create();

                    if ( ! method_exists($model, $relationship))
                    {
                        throw new \LogicException("Attempting to eager load [$relationship], but the relationship is not defined.");
                    }

                    self::eagerly($model, $results, $relationship, $relationship_args, $relationship_with);
                }
            }
            return $results;
        }


        /**
         * Eagerly load a relationship.
         *
         * @param  object  $orm
         * @param  object  $result set
         * @param  array   $parents
         * @param  string  $include
         * @return void
         */
        private static function eagerly($model, &$parents, $include, $relationship_args = array(), $relationship_with = false)
        {
            if($relationship = call_user_func_array(array($model,$include), $relationship_args)){

                $relationship->reset_relation();

                if($relationship_with) $relationship->with($relationship_with);

                // Initialize the relationship attribute on the parents. As expected, "many" relationships
                // are initialized to an array and "one" relationships are initialized to null.
                foreach ($parents as &$parent)
                {
                    $parent->relationships[$include] = (in_array($model->relating, array('has_many', 'has_many_through'))) ? array() : null;
                }

                if (in_array($relating = $model->relating, array('has_one', 'has_many', 'belongs_to')))
                {
                    return self::$relating($relationship, $parents, $model->relating_key, $include);
                }
                else
                {
                    self::has_many_through($relationship, $parents, $model->relating_key, $model->relating_table, $include);
                }
            }
        }

        /**
         * Eagerly load a 1:1 relationship.
         *
         * @param  object  $relationship
         * @param  array   $parents
         * @param  string  $relating_key
         * @param  string  $relating
         * @param  string  $include
         * @return void
         */
        private static function has_one($relationship, &$parents, $relating_key, $include)
        {
            $keys = array_keys(($parents instanceof IdiormResultSet)?$parents->as_array():$parents);

            $related = $relationship->where_in($relating_key, $keys)->find_many();
            foreach ($related as $key => $child)
            {
                if(!isset($parents[$child->$relating_key]->relationships[$include])){
                    $parents[$child->$relating_key]->relationships[$include] = $child;
                }
            }
        }


        /**
         * Eagerly load a 1:* relationship.
         *
         * @param  object  $relationship
         * @param  array   $parents
         * @param  string  $relating_key
         * @param  string  $relating
         * @param  string  $include
         * @return void
         */
        private static function has_many($relationship, &$parents, $relating_key, $include)
        {
            $keys = array_keys(($parents instanceof IdiormResultSet)?$parents->as_array():$parents);

            $related = $relationship->where_in($relating_key, $keys)->find_many();

            foreach ($related as $key => $child)
            {
                $parents[$child->$relating_key]->relationships[$include][$child->id()] = $child;
            }
        }


        // TODO;;
        /**
         * Eagerly load a 1:1 belonging relationship.
         *
         * @param  object  $relationship
         * @param  array   $parents
         * @param  string  $relating_key
         * @param  string  $include
         * @return void
         */
        private static function belongs_to($relationship, &$parents, $relating_key, $include)
        {
            foreach ($parents as &$parent)
            {
                $keys[] = $parent->$relating_key;
            }

            $children = $relationship->where_id_in(array_unique($keys))->find_many();

            foreach ($parents as &$parent)
            {
                if (array_key_exists($parent->$relating_key, $children->get_results()))
                {
                    $parent->relationships[$include] = $children[$parent->$relating_key];
                }
            }

        }

        /**
         * Eagerly load a many-to-many relationship.
         *
         * TODO: has_many_through eager load returns an array of results instead of a resultSet
         *
         * @param  object  $relationship
         * @param  array   $parents
         * @param  string  $relating_key
         * @param  string  $relating_table
         * @param  string  $include
         *
         * @return void
         */
        private static function has_many_through($relationship, &$parents, $relating_key, $relating_table, $include)
        {
            $keys = array_keys(($parents instanceof IdiormResultSet)?$parents->as_array():$parents);
            $children = $relationship->select($relating_table.".".$relating_key[0])->where_in($relating_table.'.'.$relating_key[0], $keys)->find_array();

            // The foreign key is added to the select to allow us to easily match the models back to their parents.
            // Otherwise, there would be no apparent connection between the models to allow us to match them.
            foreach ($children as $child)
            {
                // if(!isset($parents[$child[$relating_key[0]]]->relationships[$include])) {
                //     $parents[$child[$relating_key[0]]]->relationships[$include] = new IdiormResultSet();
                // }
                // create model instance of child ....
                // $parents[$child[$relating_key[0]]]->relationships[$include]->add(array($child['id']=>$child));

                $parents[$child[$relating_key[0]]]->relationships[$include][$child['id']] = $child;
            }
        }
    }
