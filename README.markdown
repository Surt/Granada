Granada
=====

This version:
No tested yet
[![Build Status](https://travis-ci.org/Surt/Paris.png?branch=master)](https://travis-ci.org/Surt/Paris)

Granada now becomes a extended version of Idiorm/Paris, adding eager and lazy loading and lot of minor additions and changes.
Update to Idiorm/Paris v1.4.0

Added eager loading
--------------------
You can use the "with" method to add relationships eager loading to the query.

```php
  $results = $user->with('avatar', 'posts')->find_many();
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


Chained relationships with arguments for eager loading!
--------------------------------------------------------
It is possible to chain relationships and add arguments to the relationships calls

```php
   // chained relationships use the "with" reserved word.
   $results = $user->with(array('posts'=>array('with'=>array('comments'))))->find_many();
   foreach($results as $result){
      foreach($posts as $post){
         echo $post->title;
         foreach($post->comments as $comment){
            echo $comment->subject;
         }
      }
   }


   // using arguments (not just one) to call the relationships
   $results = $user->with(array('posts'=>array('arg1')))->find_many();
   // will call the relationship defined in the user model with the argument "arg1"

```

Added a new way to handle filters
----------------------------------
With the __call inclusion on the ORMWrapper now you can create static functions on the model,prepended with "filter_":

```php
public static function filter_aname($orm, $argument1, $argument2...){
    return $orm->where('property', 'value')->limit('X')......;
}
```
and call it on a static call
```php
ModelName::aname($argument1, $argument2)->....
```


Lazy loading
-------------
Triying to access to a not fetched relationship will call and return it

```php
  $results = $user->find_many();
  foreach($results as $result){
      echo $result->avatar->img;
  }
```

Multiple additions names for Granada
----------------------------------
select_raw = select_expr
group_by_raw
order_by_raw
raw_join
insert : To create and save multiple elements from an array
pluck : returns a single column from the result.
find_pairs : Return array of key=>value as result
save : accepts now a boolean to use "ON DUPLICATE KEY UPDATE" (just for Mysql)
delete_many : you could use it to delete from a join clause, defining the table that you want to delete


Added overload to set and get properties
-----------------------------------------

SET
----
```php
    // In the Model
    protected function set_title($value)
    {
        $this->alias = Str::slug($value);
        $this->title = $value;
    }
```
```php
    // outside of the model
    $model->set('title', 'A title');

    // works with multiple set too
    $properties = array(
      'title'   => 'A title',
      'content' => 'Some content'
    );
    $model->set($properties);

    // try it with a direct assignement
    $model->title = 'A title';
```


GET
----
Overload of get works only on non defined or empty attributes
```php
    // In the Model
    protected function get_path(){
        return 'whatever';
    }

    ...

    // outside of the model
    echo $model->path; // returns 'whatever'
```




Basic Documentation comes from Paris:
-------------------------------------
Paris
=====

---
### Feature complete

Paris is now considered to be feature complete as of version 1.4.0. Whilst it will continue to be maintained with bug fixes there will be no further new features added.

---

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

Documentation
-------------

The documentation is hosted on Read the Docs: [paris.rtfd.org](http://paris.rtfd.org)

### Building the Docs ###

You will need to install [Sphinx](http://sphinx-doc.org/) and then in the docs folder run:

    make html

The documentation will now be in docs/_build/html/index.html

Let's See Some Code
-------------------
```php
class User extends Model {
    public function tweets() {
        return $this->has_many('Tweet');
    }
}

class Tweet extends Model {}

$user = Model::factory('User')
    ->where_equal('username', 'j4mie')
    ->find_one();
$user->first_name = 'Jamie';
$user->save();

$tweets = $user->tweets()->find_many();
foreach ($tweets as $tweet) {
    echo $tweet->text;
}
```

Changelog
---------

#### 1.4.1 - released 2013-09-05

* Increment composer.json requirement for Idiorm to 1.4.0 [[michaelward82](https://github.com/michaelward82)] - [Issue #72](https://github.com/j4mie/paris/pull/72)

#### 1.4.0 - released 2013-09-05

* Call methods against model class directly eg. `User::find_many()` - PHP 5.3 only [[Lapayo](https://github.com/Lapayo)] - [issue #62](https://github.com/j4mie/idiorm/issues/62)
* `find_many()` now returns an associative array with the databases primary ID as the array keys [[Surt](https://github.com/Surt)] - see commit [9ac0ae7](https://github.com/j4mie/paris/commit/9ac0ae7d302f1980c95b97a98cbd6d5b2c04923f) and Idiorm [issue #133](https://github.com/j4mie/idiorm/issues/133)
* Add PSR-1 compliant camelCase method calls to Idiorm (PHP 5.3+ required) [[crhayes](https://github.com/crhayes)] - [issue #59](https://github.com/j4mie/idiorm/issues/59)
* Allow specification of connection on relation methods [[alexandrusavin](https://github.com/alexandrusavin)] - [issue #55](https://github.com/j4mie/idiorm/issues/55)
* Make tests/bootstrap.php HHVM compatible [[JoelMarcey](https://github.com/JoelMarcey)] - [issue #71](https://github.com/j4mie/idiorm/issues/71)
* belongs_to doesn't work with $auto_prefix_models ([issue #70](https://github.com/j4mie/paris/issues/70))

#### 1.3.0 - released 2013-01-31

* Documentation moved to [paris.rtfd.org](http://paris.rtfd.org) and now built using [Sphinx](http://sphinx-doc.org/)
* Add support for multiple database connections [[tag](https://github.com/tag)] - [issue #15](https://github.com/j4mie/idiorm/issues/15)
* Allow a prefix for model class names - see Configuration in the documentation - closes [issues #33](https://github.com/j4mie/paris/issues/33)
* Exclude tests and git files from git exports (used by composer)
* Implement `set_expr` - closes [issue #39](https://github.com/j4mie/paris/issues/39)
* Add `is_new` - closes [issue #40](https://github.com/j4mie/paris/issues/40)
* Add support for the new IdiormResultSet object in Idiorm - closes [issue #14](https://github.com/j4mie/paris/issues/14)
* Change Composer to use a classmap so that autoloading is better supported [[javierd](https://github.com/javiervd)] - [issue #44](https://github.com/j4mie/paris/issues/44)
* Move tests into PHPUnit to match Idiorm
* Update included Idiorm version for tests
* Move documentation to use Sphinx

#### 1.2.0 - released 2012-11-14

* Setup composer for installation via packagist (j4mie/paris)
* Add in basic namespace support, see [issue #20](https://github.com/j4mie/paris/issues/20)
* Allow properties to be set as an associative array in `set()`, see [issue #13](https://github.com/j4mie/paris/issues/13)
* Patch in idiorm now allows empty models to be saved (j4mie/idiorm see [issue #58](https://github.com/j4mie/paris/issues/58))

#### 1.1.1 - released 2011-01-30

* Fix incorrect tests, see [issue #12](https://github.com/j4mie/paris/issues/12)

#### 1.1.0 - released 2011-01-24

* Add `is_dirty` method

#### 1.0.0 - released 2010-12-01

* Initial release