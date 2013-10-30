<?php namespace Granada;

use ArrayAccess;

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
     * Model base class. Your mod el objects should extend
     * this class. A minimal subclass would look like:
     *
     * class Widget extends Model {
     * }
     *
     */
    class Granada implements ArrayAccess  {

        // Default ID column for all models. Can be overridden by adding
        // a public static _id_column property to your model classes.
        const DEFAULT_ID_COLUMN = 'id';

        // Default foreign key suffix used by relationship methods
        const DEFAULT_FOREIGN_KEY_SUFFIX = '_id';

        public static $resultSetClass = 'Granada\ResultSet';

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
                   Orm\Wrapper::DEFAULT_CONNECTION
               );
            }
            $wrapper = Orm\Wrapper::for_table($table_name, $connection_name);
            $wrapper->set_class_name($class_name);
            $wrapper->use_id_column(self::_get_id_column_name($class_name));
            $wrapper->resultSetClass = $class_name::$resultSetClass;
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
                ->where("{$join_table_name}.{$key_to_base_table}", $this->$base_table_id_column)
                ->non_associative();
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
            elseif(array_key_exists($property, $this->relationships))
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
            $this->set($property, $value);
        }

        /**
         * Magic isset method, allows isset($model->property) to work correctly.
         */
        public function __isset($property) {
            return (array_key_exists($property, $this->relationships) || $this->orm->__isset($property) || method_exists($this, $method = 'get_'.$property));
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
                    $property[$field] = $this->$method($val);
                }
                elseif(!is_array($property) && method_exists($this, $property)){
                    $this->relationships[$property] = $value;
                }
            }
            $result = $this->orm->set($property, $value);
            return $result;
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
        public function save($ignore = false) {
            return $this->orm->save($ignore);
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

        public function get_resultSetClass(){
            return static::$resultSetClass;
        }

        /**
         * Calls static methods directly on the Orm\Wrapper
         *
         */
        public static function __callStatic($method, $parameters) {
            if(function_exists('get_called_class')) {
                $model = self::factory(get_called_class());
                return call_user_func_array(array($model, $method), $parameters);
            }
        }
    }