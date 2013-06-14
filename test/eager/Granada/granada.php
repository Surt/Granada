<?php

/**
 * @author Erik Wiesenthal
 * @email erikwiesenthal@hotmail.com
 * @project Granada
 * @copyright 2012
 * 
 * Granada is a fork from https://github.com/powerpak/dakota
 * Minor changes + eager loading
 * 
 * Subclass of Idiorm's ORM class that supports
 * returning instances of a specified class rather
 * than raw instances of the ORM class.
 *
 * You shouldn't need to interact with this class
 * directly. It is used internally by the Model base
 * class.
 */
class ORMWrapper extends ORM
{

    /**
     * The wrapped find_one and find_many classes will
     * return an instance or instances of this class.
     */
    protected $_class_name;

    /**
     * Constructs a wrapper for a table.  Called by Model instances.
     */
    public function __construct($table_name)
    {
        self::_setup_db();
        parent::__construct($table_name);

        // Until find_one or find_many are called, this object is considered a new row
        $this->create();
    }

    /**
     * Set the name of the class which the wrapped
     * methods should return instances of.
     */
    public function set_class_name($class_name)
    {
        $this->_class_name = $class_name;
    }

    /**
     * Start a transaction on the database (if supported)
     */
    public static function start_transaction()
    {
        self::$_db->beginTransaction();
    }

    /**
     * Commits a transaction on the database (if supported)
     */
    public static function commit()
    {
        self::$_db->commit();
    }

    /**
     * Rolls back a transaction on the database (if supported)
     */
    public static function rollback()
    {
        self::$_db->rollBack();
    }

    /**
     * Rewrite Idiorm's for_table factory so it returns models of the
     * actual $_class_name
     */
    private function _for_table($table_name)
    {
        self::_setup_db();
        return new $this->_class_name();
    }

    /**
     * Mostly for convenience in chaining.
     */
    public function not_new()
    {
        $this->_is_new = FALSE;
        return $this;
    }

    /**
     * Override Idiorm's find_one method to return
     * the current instance hydrated with the result,
     * or just the current instance if there was no result.
     */
    public function find_one($id = null)
    {
        if (!is_null($id)) {
            $this->where_id_is($id);
        }
        $rows = $this->limit(1)->_run();
        if ($rows) {
            $id_column = (isset($rows[0][$this->_get_id_column_name()])) ? $this->_get_id_column_name() : $this::DEFAULT_ID_COLUMN;
            $key = (isset($rows[0][$id_column])) ? $rows[0][$id_column] : 0;
            $instances = array($key => $this->hydrate($rows[0])->not_new());
            $instances = Eager::hydrate($this, $instances);
        } else {
            $id_column = $this::DEFAULT_ID_COLUMN;
            $instances = array();
        }

        return $rows ? $instances[$key] : $this;
    }

    /**
     * Override Idiorm's find_many method to return
     * an array of many instances of the current instance's
     * class.
     * 
     * Added the array result key = primary key from the model
     * 
     */
    public function find_many()
    {
        $rows = $this->_run();

        $size = count($rows);
        $instances = array();
        for ($i = 0; $i < $size; $i++) {
            $row = $this->_for_table($this->_table_name)
                    ->use_id_column($this->_instance_id_column)
                    ->hydrate($rows[$i])
                    ->not_new();
            $key = (isset($row->{$this->_instance_id_column})) ? $row->{$this->_instance_id_column} : $i;
            $instances[$key] = $row;
        }

        return $instances ? Eager::hydrate($this, $instances) : array();
    }

    public function where_id_in($elements = array())
    {
        return $this->where_in($this->_instance_id_column, $elements);
    }

    /**
     * Return array as result, no instance needed
     * 
     */
    public function find_array()
    {
        return $this->_run();
    }

    /**
     * Return pairs as result, no instance needed
     * 
     */
    public function find_pairs($key = false, $value = false)
    {
        $key = ($key) ? $key : 'pk';
        $value = ($value) ? $value : 'name';
        return Surt_Array::assoc_to_keyval($this->select_raw("$key,$value")->order_by_asc($value)->find_array(), $key, $value);
    }

    /**
     * Did we load any rows from the last query?
     * This is because we no longer return false from find_one();
     * the object is always representative of a potential database row.
     */
    public function loaded()
    {
        return (!$this->_is_new) ? $this : false;
    }

    public function reset_relation()
    {
        array_shift($this->_where_conditions);
        return $this;
    }

    /**
     * 
     * To save multiple elements, easy way
     * Using an array with rows array(array('name'=>'value',...), array('name2'=>'value2',...),..) 
     * or a array multiple
     * 
     */
    public function insert($rows)
    {
        self::$_db->beginTransaction();
        foreach ($rows as $row) {
            $this->_for_table($this->_table_name)->use_id_column($this->_instance_id_column)->create($row)->save();
        }
        self::$_db->commit();
        return $this->get_db()->lastInsertId();
    }

    /**
     * 
     * To save multiple elements, easy way
     * Using multiple arrays array('name'=>'value',...), array('name2'=>'value2',...) 
     * or a array multiple
     * 
     */
    public function insert_multiple($rows)
    {
        self::$_db->beginTransaction();
        $rows = func_get_args();
        foreach ($rows as $row) {
            $this->_for_table($this->_table_name)->use_id_column($this->_instance_id_column)->create($row)->save();
        }
        self::$_db->commit();
        return $this->get_db()->lastInsertId();
    }

    /**
     * 
     * Update multiple properties at once for one instance
     * 
     */
    public function update($data)
    {
        return $this->set($data)->save();
    }

}

class Model extends ORMWrapper
{

    /**
     * =============
     * CONFIGURATION
     * =============
     */

    /**
     * Default ID column name for all models. 
     * Can be overridden by adding a public static _id_column property to your model classes.
     * @var string 
     */
    const DEFAULT_ID_COLUMN = 'id';

    /**
     * Default foreign key suffix used by relationship methods
     * @var string
     */
    const DEFAULT_FOREIGN_KEY_SUFFIX = '_id';

    /**
     * Array of aliases attributes to database fields 
     * @example array('db_field' => 'attribute_aliase');
     * @var array
     */
    public static $_aliases_fields_map = array();
    
    /**
     * Array of database fields whith aliases attributes
     * @example array('attribute_aliase' => 'db_field');
     * @var array 
     */
    public static $_aliases_attributes_map = array();
    
    /**
     * The model's ignored attributes.
     * Ignored attributes will not be saved to the database, 
     * and are primarily used to hold relationships.
     * @var array
     */
    public $ignore = array();

    /**
     * The relationships that should be eagerly loaded.
     *
     * @var array
     */
    public $includes = array();

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
     * This is used during many-to-many eager loading.
     *
     * @var string
     */
    public $relating_table;
    
    /**
     * Improve performance with several caches
     */
    protected static $cache = array(
        'className' => array(),
        'tableName' => array()
    );

    /**
     * =====================
     * CONSTRUCTOR & FACTORY
     * =====================
     */
    
     /**
     * Magic function so that new operator works as expected
     */
    public function __construct()
    {
        //map the aliases fields
        self::_map_aliases();
        
        //get class name
        $class_name = get_called_class();
        
        //get table name
        $table_name = self::_get_table_name($class_name);
        
        //parent constructor
        parent::__construct($table_name);
        
        //set class name
        $this->set_class_name($class_name);
        
        //set id column
        $this->use_id_column(self::_id_column_name($class_name));
    }

    
    /**
     * Factory method used to acquire instances of the given class.
     * The class name should be supplied as a string, and the class
     * should already have been loaded by PHP (or a suitable autoloader
     * should exist).  Basically a wrapper for the new operator to facilitate
     * chaining.
     */
    public static function factory($class_name)
    {
        return new $class_name;
    }

    
    /**
     * Set the eagerly loaded models on the queryable model.
     *
     * @return Model
     */
    public function with()
    {
        $this->includes = func_get_args();

        return $this;
    }

    /**
     * Static method to get a table name given a class name.
     * If the supplied class has a public static property
     * named $_table, the value of this property will be
     * returned. If not, the class name will be converted using
     * the _class_name_to_table_name method method.
     */
    protected static function _get_table_name($class_name)
    {
        if(!isset(self::$cache['tableName'][$class_name]))  
            self::$cache['tableName'][$class_name] = (isset(static::$_table)) ? static::$_table : self::_class_name_to_table_name($class_name);
        
        return self::$cache['tableName'][$class_name];
    }

    /**
     * Static method to convert a class name in CapWords
     * to a table name in lowercase_with_underscores.
     * For example, CarTyre would be converted to car_tyre.
     */
    protected static function _class_name_to_table_name($class_name)
    {
        //caching process
        if(!isset(self::$cache['className'][$class_name]))
            self::$cache['className'][$class_name] = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $class_name));
        
        return self::$cache['className'][$class_name];
    }

    /**
     * Return the ID column name to use for this class. If it is
     * not set on the class, returns null.
     */
    protected static function _id_column_name($class_name)
    {
        return (isset(static::$_id_column)) ? static::$_id_column : self::DEFAULT_ID_COLUMN;
    }

    /**
     * Build a foreign key based on a table name. If the first argument
     * (the specified foreign key column name) is null, returns the second
     * argument (the name of the table) with the default foreign key column
     * suffix appended.
     */
    protected static function _build_foreign_key_name($specified_foreign_key_name, $table_name)
    {
        if (!is_null($specified_foreign_key_name)) {
            return $specified_foreign_key_name;
        }
        return $table_name . self::DEFAULT_FOREIGN_KEY_SUFFIX;
    }

    protected function has_none()
    {
        return self::factory('None');
    }

    /**
     * Internal method to construct the queries for both the has_one and
     * has_many methods. These two types of association are identical; the
     * only difference is whether find_one or find_many is used to complete
     * the method chain.
     */
    protected function _has_one_or_many($associated_class_name, $foreign_key_name = null)
    {
        $base_table_name = self::_get_table_name(get_class($this));
        $foreign_key_name = self::_build_foreign_key_name($foreign_key_name, $base_table_name);
        $this->relating_key = $foreign_key_name;
        return self::factory($associated_class_name)->where($foreign_key_name, $this->id());
    }

    /**
     * Helper method to manage one-to-one relations where the foreign
     * key is on the associated table.
     */
    protected function has_one($associated_class_name, $foreign_key_name = null)
    {
        $this->relating = 'has_one';
        return $this->_has_one_or_many($associated_class_name, $foreign_key_name);
    }

    /**
     * Helper method to manage one-to-many relations where the foreign
     * key is on the associated table.
     */
    protected function has_many($associated_class_name, $foreign_key_name = null)
    {
        $this->relating = 'has_many';
        return $this->_has_one_or_many($associated_class_name, $foreign_key_name);
    }

    /**
     * Helper method to manage one-to-one and one-to-many relations where
     * the foreign key is on the base table.
     */
    protected function belongs_to($associated_class_name, $foreign_key_name = null)
    {
        $this->relating = 'belongs_to';
        $associated_table_name = self::_get_table_name($associated_class_name);
        $foreign_key_name = self::_build_foreign_key_name($foreign_key_name, $associated_table_name);
        $associated_object_id = $this->$foreign_key_name;
        $this->relating_key = $foreign_key_name;
        return self::factory($associated_class_name)->where_id_is($associated_object_id);
    }

    /**
     * Helper method to manage many-to-many relationships via an intermediate model. See
     * README for a full explanation of the parameters.
     */
    protected function has_many_through($associated_class_name, $join_class_name = null, $key_to_base_table = null, $key_to_associated_table = null)
    {
        $this->relating = 'has_many_through';
        $base_class_name = get_class($this);

        // The class name of the join model, if not supplied, is
        // formed by concatenating the names of the base class
        // and the associated class, in alphabetical order.
        if (is_null($join_class_name)) {
            $class_names = array($base_class_name, $associated_class_name);
            sort($class_names, SORT_STRING);
            $join_class_name = join("", $class_names);
        }

        // Get table names for each class
        $base_table_name = self::_get_table_name($base_class_name);
        $associated_table_name = self::_get_table_name($associated_class_name);
        $join_table_name = self::_get_table_name($join_class_name);

        // Get ID column names
        $base_table_id_column = self::_id_column_name($base_class_name);
        $associated_table_id_column = self::_id_column_name($associated_class_name);

        // Get the column names for each side of the join table
        $key_to_base_table = self::_build_foreign_key_name($key_to_base_table, $base_table_name);
        $key_to_associated_table = self::_build_foreign_key_name($key_to_associated_table, $associated_table_name);

        $this->relating_key = array(
            $key_to_base_table,
            $key_to_associated_table
        );
        $this->relating_table = $join_table_name;

        return self::factory($associated_class_name)
                        ->select("{$associated_table_name}.*")
                        ->join($join_table_name, array("{$associated_table_name}.{$associated_table_id_column}", '=', "{$join_table_name}.{$key_to_associated_table}"))
                        ->where("{$join_table_name}.{$key_to_base_table}", $this->id());
    }

    /**
     * =================
     * GETTERS & SETTERS
     * =================
     */
    
    /**
     * Magic method for retrieving model attributes.
     * 
     * @param string $key attribute's name
     * @return mixed
     */
    public function get($key)
    {
        //aliases support
        $key = self::_get_field_by_column($key);
        
        if (method_exists($this, 'get_' . $key)) {
            return $this->{'get_' . $key}();
        } elseif (array_key_exists($key, $this->_data)) {
            return $this->_data[$key];
        }
        // Is the requested item a model relationship that has already been loaded?
        // All of the loaded relationships are stored in the "ignore" array.
        elseif (array_key_exists($key, $this->ignore)) {
            return $this->ignore[$key];
        }
        // Is the requested item a model relationship? If it is, we will dynamically
        // load it and return the results of the relationship query.
        elseif (method_exists($this, $key)) {
            if ($key != self::_id_column_name(get_class($this))) {
                $relation = $this->$key();
                return $this->ignore[$key] = (in_array($this->relating, array('has_one', 'belongs_to'))) ? $relation->find_one() : $relation->find_many();
            }
            else
                return false;
        }
        else
            return false;
    }

    /**
     * Set a property to a particular value on this object.
     * Flags that property as 'dirty' so it will be saved to the
     * database when save() is called.
     * 
     * @param string $key attribute's name
     * @param mixed $value attribute's value
     * @return mixed
     */
    public function set($key, $value = null)
    {
        
        //aliases support
        $key = self::_get_field_by_column($key);
        
        if (!is_array($key)) {
            $key = array($key => $value);
        }
        foreach ($key as $field => $value) {
            $this->_data[$field] = $value;
            $this->_dirty_fields[$field] = $value;
        }
        return $this;
    }

    /**
     * =============
     * MAGIC METHODS 
     * =============
     */
    
    /**
     * Magic method to get model attributes.
     * Added for aliases support
     * @param string $key attribute's name
     * @return mixed 
     */
    public function __get($key)
    {
        //aliases support
        $key = self::_get_field_by_column($key);
        return $this->get($key);
    }
    
    
    /**
     * Magic Method for setting model attributes.
     * @param string $key attribute's name
     * @param mixed attribute's value
     * @return mixed
     */
    public function __set($key, $value)
    {
        //aliases support
        $key = self::_get_field_by_column($key);
        
        // If the key is a relationship, add it to the ignored attributes.
        // Ignored attributes are not stored in the database.
        if (method_exists($this, $key)) {
            $this->ignore[$key] = $value;
        } else {
            return $this->set($key, $value);
        }
    }

    /**
     * Magic Method for determining if a model attribute is set.
     * @param string $key attribute's name
     * @return boolean 
     */
    public function __isset($key)
    {
        //aliases support
        $key = self::_get_field_by_column($key);
        
        return (array_key_exists($key, $this->_data) or array_key_exists($key, $this->ignore));
    }

    /**
     * Magic Method for unsetting model attributes.
     */
    public function __unset($key)
    {
        //aliases support
        $key = self::_get_field_by_column($key);
        
        unset($this->_data[$key], $this->ignore[$key], $this->_dirty_fields[$key]);
    }
    
    /**
     * =================
     * ALIASES FUNCTIONS
     * =================
     */
    
    /**
     * Return the column name of the associated field
     * @param string $field_name field's name
     * @return string column's name
     */
    protected static function _get_column_by_field($field_name)
    {
        return (isset(static::$_aliases_fields_map[$field_name])) ? static::$_aliases_fields_map[$field_name] : $field_name;
    }
    /**
     * Return the field associated at the aliase attribute
     * @param string $column_name
     * @return string 
     */
    protected static function _get_field_by_column($column_name)
    {
        return (isset(static::$_aliases_attributes_map[$column_name])) ? static::$_aliases_attributes_map[$column_name] : $column_name;
    }
    
    /**
     * populate aliases mapping by revert key/values from the other mapping
     */
    private static function _map_aliases()
    {
        if(empty(static::$_aliases_attributes_map))
            if(!empty(static::$_aliases_fields_map))
                static::$_aliases_attributes_map = array_flip (static::$_aliases_fields_map);
            
        if(empty(static::$_aliases_fields_map))
            if(!empty(static::$_aliases_attributes_map))
                static::$_aliases_fields_map = array_flip (static::$_aliases_attributes_map);
    }
    
    /**
     * ===================
     * SERIALIZE FUNCTIONS
     * ===================
     */
    
    /**
     * for PHP 5.4 jsonSerializable interface
     * @return array
     */
    public function jsonSerialize() {
        return $this->_data;
    }
    
    /**
     * transforme object to Json
     * @return string json encoded
     */
    public function getJson(){
        return json_encode($this->jsonSerialize());
    }

}

class None extends Model
{

    public function find_one($id = null)
    {
        return array();
    }

    public function find_many()
    {
        return array();
    }

    public function loaded()
    {
        return false;
    }

    public function with()
    {
        return $this;
    }

}