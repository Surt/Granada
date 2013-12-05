Granada
=====

[![Latest Stable Version](https://poser.pugx.org/surt/granada/v/stable.png)](https://packagist.org/packages/surt/granada)
[![Build Status](https://travis-ci.org/Surt/Granada.png?branch=develop)](https://travis-ci.org/Surt/Granada)
[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/Surt/granada/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

Granada is a easy to use Active Record implementation, and ORM based on Idiorm/Paris.

A quick view:
------------


```php

use Granada\Model;

class User extends Model {
    public function posts() {
        return $this->has_many('Post');
    }
}

class Post extends Model {}


// select
$user = User::where('name', 'John')->find_one();

// modify
$user->first_name = 'Doe';
$user->save();

// select relationship
$posts_list = $user->posts()->find_many();
foreach ($posts as $post) {
    echo $post->content;
}

```
You can read the Paris Docs on [paris.readthedocs.org](http://paris.rtfd.org) but be sure to read the additions below.


Install
-------
Using composer:
```
  "require": {

        "surt/granada": "dev-master"

    }
```

Configure it:
```php
require 'vendor/autoload.php';

use Granada\ORM;

ORM::configure('mysql:host=localhost;dbname=my_database');
ORM::configure('username', 'database_user');
ORM::configure('password', 'top_secret');

```

As always, you can check it in detail on [Paris documentation](http://idiorm.readthedocs.org/en/latest/configuration.html#setup)


Aditions
--------

### Eager loading
You can use the "with" method to add relationships eager loading to the query.

```php
  $results = User::with('avatar', 'posts')->find_many();
```
will use 3 querys to fetch the users and the relationships:
```sql
  SELECT * FROM user
  SELECT * FROM avatar WHERE user_id IN (....)
  SELECT * FROM posts WHERE user_id IN (....)
```
It is possible to get the relationships results for each result, this way

```php
  foreach($results as $result){
      echo $result->avatar->img;
      foreach($result->posts as $post){
         echo $post->title;
      }
  }
```
---

### Lazy loading

Triying to access to a not fetched relationship will call and return it

```php
  $results = User::find_many();
  foreach($results as $result){
      echo $result->avatar->img;
  }
```

Notice that if there is no result for `avatar` on the above example it will throw a `Notice: Trying to get property of non-object...`
Note:  Maybe worth the effort to create a NULL object for this use case and others.

---


### Chained relationships with arguments for eager loading!

It is possible to chain relationships and add arguments to the relationships calls

```php

   // chained relationships with dot notation
   $results = User::with('posts.comments')->find_many();

   // OR

   // chained relationships use the "with" reserved word. (usefull if you want to pass arguments to the relationships)
   $results = User::with(array('posts'=>array('with'=>array('comments'))))->find_many();

    // SELECT * FROM user
    // SELECT * FROM post WHERE user_id IN (....)
    // SELECT * FROM comments WHERE post_id IN (....)

   foreach($results as $result){
      foreach($posts as $post){
         echo $post->title;
         foreach($post->comments as $comment){
            echo $comment->subject;
         }
      }
   }


   // you can use arguments (one or more) to call the models relationships
   $results = User::with(array('posts'=>array('arg1')))->find_many();
   // will call the relationship defined in the user model with the argument "arg1"

```
---
### Custom query filters

It's possible to create static functions on the model to work as filter in queries. Prepended it with "filter_":

```php
use Granada\Model;

class ModelName extends Model {
    ....
    public static function filter_aname($query, $argument1, $argument2...){
        return $query->where('property', 'value')->limit('X')......;
    }
    ....
}
```
and call it on a static call
```php
ModelName::aname($argument1, $argument2)->....
```

---
### Multiple additions names for Granada
- select_raw
- group_by_raw
- order_by_raw
- raw_join
- insert : To create and save multiple elements from an array
- pluck : returns a single column from the result.
- find_pairs : Return array of key=>value as result
- save : accepts a boolean to use "ON DUPLICATE KEY UPDATE" (just for Mysql)
- delete_many (accept join clauses)


---
### Overload SET

```php
    // In the Model
    protected function set_title($value)
    {
        $this->alias = Str::slug($value);
        return $value;
    }
```
```php
    // outside of the model
    $content_instance->set('title', 'A title');

    // works with multiple set too
    $properties = array(
      'title'   => 'A title',
      'content' => 'Some content'
    );
    $content_instance->set($properties);

    // try it with a direct assignement
    $content_instance->title = 'A title';
```

---
### Overload GET and MISSING property

 
```php
    // In the Model
    
    // Work on defined
    protected function get_path($value)
    {
        return strtolower($value);
    }

    // and non-defined attributes.
    protected function mising_testing()
    {
        return 'whatever';
    }
    ...

    // outside of the model
    echo $content_instance->path; // returns the lowercase path value of $content_instance 
    echo $content_instance->testing; // returns 'whatever' since we defined a missing_{attribute_name}
```

Of course, you still can define functions with the property name if you want to overload it completely.

---
### Define resultSet (collection type) class on Model

Now is possible to define the resultSet class returned for a model instances result. (if `return_result_sets` config variable is set to true)
Notice that the resultSet class defined must `extends Granada\ResultSet` and must be loaded

```php
    // In the Model
    public static $resultSetClass = 'TreeResultSet';
```
```php
    // outside of the model
    var_dump(Content::find_many());

    // echoes
    object(TreeResultSet)[10]
        protected '_results' => array(...)
    ....
```

ResultSets are defined by the model in the result, as you can see above.
On eager load, the results are consistent.
For example, if we have a `Content` model, with `$resultSetClass = 'TreeResultSet'` and a `has_many` relationship defined as `media`:

```php

  Content::with('media')->find_many();

```
will return a `TreeResultSet` with instances of `Content` each with a `property $media` containing `Granada\ResultSet` (the default resultSet if none if defined on the Model)

---
Basic Documentation comes from Paris:
-------------------------------------
Paris
=====

### Feature complete

Paris is now considered to be feature complete as of version 1.4.0. Whilst it will continue to be maintained with bug fixes there will be no further new features added.


A lightweight Active Record implementation for PHP5.

Built on top of [Idiorm](http://github.com/j4mie/idiorm/).

Tested on PHP 5.2.0+ - may work on earlier versions with PDO and the correct database drivers.

Released under a [BSD license](http://en.wikipedia.org/wiki/BSD_licenses).

Features
--------

* Extremely simple configuration.
* Exposes the full power of [Idiorm](http://github.com/j4mie/idiorm/)'s fluent query API.
* Supports associations.
* Simple mechanism to encapsulate common queries in filter methods.
* Built on top of [PDO](http://php.net/pdo).
* Uses [prepared statements](http://uk.php.net/manual/en/pdo.prepared-statements.php) throughout to protect against [SQL injection](http://en.wikipedia.org/wiki/SQL_injection) attacks.
* Database agnostic. Currently supports SQLite, MySQL, Firebird and PostgreSQL. May support others, please give it a try!
* Supports collections of models with method chaining to filter or apply actions to multiple results at once.
* Multiple connections are supported




