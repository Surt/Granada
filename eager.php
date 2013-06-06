<?php
/**
 * @author Erik Wiesenthal
 * @email erikwiesenthal@hotmail.com
 * @project Granada
 * @copyright 2012
 *
 * Mashed from eloquent https://github.com/taylorotwell/eloquent
 * to works with idiorm + https://github.com/powerpak/dakota
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
    		foreach ($orm->includes as $include)
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

                /*
    			if ( ! method_exists($model, $relationship))
    			{
    				throw new \LogicException("Attempting to eager load [$relationship], but the relationship is not defined.");
    			}
    			*/

    			static::eagerly($orm, $results, $relationship, $relationship_args, $relationship_with);
    		}
        }
        return $results;
	}


	/**
	 * Eagerly load a relationship.
	 *
<<<<<<< HEAD
	 * @param  object  $orm
=======
	 * @param  object  $eloquent
>>>>>>> 311b14621aa05d0e29f90abad79dfd6136a90e04
	 * @param  array   $parents
	 * @param  string  $include
	 * @return void
	 */
	private static function eagerly($orm, &$parents, $include, $relationship_args = array(), $relationship_with = false)
	{
		$model = $orm->create();

	    if($relationship = call_user_func_array(array($model,$include),$relationship_args)){

    		$relationship->reset_relation();

            if($relationship_with) $relationship->with($relationship_with);

    		// Initialize the relationship attribute on the parents. As expected, "many" relationships
    		// are initialized to an array and "one" relationships are initialized to null.
    		foreach ($parents as &$parent)
    		{
    			$parent->ignore[$include] = (in_array($model->relating, array('has_many', 'has_and_belongs_to_many'))) ? array() : null;
    		}

    		if (in_array($relating = $model->relating, array('has_one', 'has_many', 'belongs_to')))
    		{
    			return static::$relating($relationship, $parents, $model->relating_key, $include);
    		}
    		else
    		{
    			static::has_and_belongs_to_many($relationship, $parents, $model->relating_key, $model->relating_table, $include);
    		}
        }
	}


    public static function has_none(){
        return false;
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
	    $related = $relationship->where_in($relating_key, array_keys($parents))->group_by($relating_key)->find_many();
        foreach ($related as $key => $child)
		{
            $parents[$child->$relating_key]->ignore[$include] = $child;
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
	    $related = $relationship->where_in($relating_key, array_keys($parents))->find_many();

		foreach ($related as $key => $child)
		{
			$parents[$child->$relating_key]->ignore[$include][$child->id()] = $child;
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
		//$related = $relationship->where_id_in(array_keys($parents))->find_many();

        foreach ($parents as &$parent)
        {
            $keys[] = $parent->$relating_key;
        }

        $children = $relationship->where_id_in(array_unique($keys))->find_many();

        foreach ($parents as &$parent)
        {
            if (array_key_exists($parent->$relating_key, $children))
            {
                $parent->ignore[$include] = $children[$parent->$relating_key];
            }
        }
    }









	/**
	 * Eagerly load a many-to-many relationship.
	 *
	 * @param  object  $relationship
	 * @param  array   $parents
	 * @param  string  $relating_key
	 * @param  string  $relating_table
	 * @param  string  $include
	 *
	 * @return void
	 */
	private static function has_and_belongs_to_many($relationship, &$parents, $relating_key, $relating_table, $include)
	{
		$children = $relationship->select($relating_table.".".$relating_key[0])->where_in($relating_table.'.'.$relating_key[0], array_keys($parents))->find_many();

		// The foreign key is added to the select to allow us to easily match the models back to their parents.
		// Otherwise, there would be no apparent connection between the models to allow us to match them.

		foreach ($children as $child)
		{
			$parents[$child->$relating_key[0]]->ignore[$include][$child->id()] = $child;
		}
	}
}