<?php namespace Granada;

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
        public static function hydrate($orm, &$results, $return_result_set = false)
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
                    $model = $orm->create();

                    if (!method_exists($model, $relationship))
                    {
                        throw new \LogicException("Attempting to eager load [$relationship], but the relationship is not defined.");
                    }

                    self::eagerly($model, $results, $relationship, $relationship_args, $relationship_with, $return_result_set);
                }
            }
            return $results;
        }

        public static function getKeys($parents){
            $keys = array();
            $parents = ($parents instanceof ResultSet)?$parents->as_array():$parents;

            if(key($parents) === 0)  {
                $count = count($parents);
                for($i=0; $i<$count; $i++){
                    $keys[] = $parents[$i]->id;
                }
            }
            else {
                $keys = array_keys($parents);
            }

            return $keys;
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
        private static function eagerly($model, &$parents, $include, $relationship_args = array(), $relationship_with = false, $return_result_set)
        {
            if($relationship = call_user_func_array(array($model,$include), $relationship_args)){

                $relationship->reset_relation();

                if($relationship_with) $relationship->with($relationship_with);

                // Initialize the relationship attribute on the parents. As expected, "many" relationships
                // are initialized to an array and "one" relationships are initialized to null.
                // added: many relationships are reset to array since we don't know yet the resultSet applicable
                foreach ($parents as &$parent)
                {
                    $parent->relationships[$include] = (in_array($model->relating, array('has_many', 'has_many_through'))) ? array() : null;
                }

                if (in_array($relating = $model->relating, array('has_one', 'has_many', 'belongs_to')))
                {
                    return self::$relating($relationship, $parents, $model->relating_key, $include, $return_result_set);
                }
                else
                {
                    self::has_many_through($relationship, $parents, $model->relating_key, $model->relating_table, $include, $return_result_set);
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
        private static function has_one($relationship, &$parents, $relating_key, $include, $return_result_set)
        {
            $keys = static::getKeys($parents);
            $related = $relationship->where_in($relating_key, $keys)->find_many();

            // if parents is not a associative array
            if(key(reset($parents)) === 0)  {
                $results = array();
                foreach ($related as $key => $child)
                {
                    if(!isset($results[$child[$relating_key]])){
                        $results[$child[$relating_key]] = $child;
                    }
                }

                foreach($parents as $p_key=>$parent){
                    foreach($results as $r_key=>$result){
                        if($parent->id == $r_key){
                           $parents[$p_key]->relationships[$include] = $result;
                        }
                    }
                }
            }
            else {
                foreach ($related as $key => $child)
                {
                    if(!isset($parents[$child->$relating_key]->relationships[$include])){
                        $parents[$child->$relating_key]->relationships[$include] = $child;
                    }
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
        private static function has_many($relationship, &$parents, $relating_key, $include, $return_result_set)
        {
            $keys = static::getKeys($parents);
            $related = $relationship->where_in($relating_key, $keys)->find_many();

            // if parents is not a associative array
            if(key(reset($parents)) === 0)  {
                $results = array();
                foreach ($related as $key => $child)
                {
                    if(empty($results[$child[$relating_key]]) && $return_result_set){
                        $resultSetClass = $child->get_resultSetClass();
                        $results[$child[$relating_key]] = new $resultSetClass();
                    }
                    $results[$child[$relating_key]][$child->id] = $child;
                }

                foreach($parents as $p_key=>$parent){
                    foreach($results as $r_key=>$result){
                        if($parent->id == $r_key){
                           $parents[$p_key]->relationships[$include] = $result;
                        }
                    }
                }
            }
            else {
                // if parents is an associative array
                foreach ($related as $key => $child)
                {
                    // if resultSet must be returned, create it if the relationships key is not defined
                    if(empty($parents[$child[$relating_key]]->relationships[$include]) && $return_result_set){
                        $resultSetClass = $child->get_resultSetClass();
                        $parents[$child->$relating_key]->relationships[$include] = new $resultSetClass();
                    }
                    // add the instance to the relationship array-resultSet
                    $parents[$child->$relating_key]->relationships[$include][$child->id()] = $child;
                }
            }
        }


        /**
         * Eagerly load a 1:1 belonging relationship.
         *
         * @param  object  $relationship
         * @param  array   $parents
         * @param  string  $relating_key
         * @param  string  $include
         * @return void
         */
        private static function belongs_to($relationship, &$parents, $relating_key, $include, $return_result_set)
        {
            foreach ($parents as &$parent)
            {
                $keys[] = $parent->$relating_key;
            }

            $children = $relationship->where_id_in(array_unique($keys))->find_many();
            if($children  instanceof ResultSet) $children = $children->as_array();

            foreach ($parents as &$parent)
            {
                if (array_key_exists($parent->$relating_key, $children))
                {
                    $parent->relationships[$include] = $children[$parent->$relating_key];
                }
            }
        }

        /**
         * Eagerly load a many-to-many relationship.
         *
         *
         * @param  object  $relationship
         * @param  array   $parents
         * @param  string  $relating_key
         * @param  string  $relating_table
         * @param  string  $include
         *
         * @return void
         */
        private static function has_many_through($relationship, &$parents, $relating_key, $relating_table, $include, $return_result_set)
        {
            $keys = static::getKeys($parents);

            // The foreign key is added to the select to allow us to easily match the models back to their parents.
            // Otherwise, there would be no apparent connection between the models to allow us to match them.
            $children = $relationship->select($relating_table.".".$relating_key[0])->where_in($relating_table.'.'.$relating_key[0], $keys)
                                     ->non_associative()
                                     ->find_many();

            foreach ($children as $child)
            {
                $related = $child[$relating_key[0]];
                unset($child[$relating_key[0]]);  // foreign key does not belongs to the related model

                if(empty($parents[$related]->relationships[$include]) && $return_result_set){
                    $resultSetClass = $child->get_resultSetClass();
                    $parents[$related]->relationships[$include] = new $resultSetClass();
                }
                // no associative result sets for has_many_through, so we can have multiple rows with the same primary_key
                $parents[$related]->relationships[$include][] = $child;
            }
        }
    }
