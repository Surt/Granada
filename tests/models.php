<?php

use Granada\Model;

/**
 * Models for use during test of eager loading
 *
 * @author Peter Schumacher <peter@schumacher.dk>
 */

class Manufactor extends Model {
    public function cars() {
        return $this->has_many('Car');
    }
}

class Owner extends Model {
    public function car() {
        return $this->has_one('Car');
    }
}

class Part extends Model {
    public function cars() {
        return $this->has_many_through('Car');
    }
}

class Car extends Model {
    public function manufactor() {
        return $this->belongs_to('Manufactor');
    }

    public function owner() {
        return $this->belongs_to('Owner');
    }

    public function parts() {
        return $this->has_many_through('Part');
    }

    public function get_existingProperty($value){
        return strtolower($value);
    }

    public function get_nonExistentProperty(){
        return "test";
    }

    public function missing_someProperty(){
        return 'This property is missing';
    }

    public function set_name($value){
        return 'test';
    }

    public static function filter_byName($query, $name){
        return $query->where('name', $name);
    }
 }

class CarPart extends Model { }



/**
 * Models for use during testing
 */
class Simple extends Model { }
class ComplexModelClassName extends Model { }
class ModelWithCustomTable extends Model {
    public static $_table = 'custom_table';
}
class ModelWithCustomTableAndCustomIdColumn extends Model {
    public static $_table = 'custom_table';
    public static $_id_column = 'custom_id_column';
}
class ModelWithFilters extends Model {
    public static function name_is_fred($orm) {
        return $orm->where('name', 'Fred');
    }
    public static function name_is($orm, $name) {
        return $orm->where('name', $name);
    }
}
class ModelWithCustomConnection extends Model {
    const ALTERNATE = 'alternate';
    public static $_connection_name = self::ALTERNATE;
}

class Profile extends Model {
    public function user() {
        return $this->belongs_to('User');
    }
}
class User extends Model {
    public function profile() {
        return $this->has_one('Profile');
    }
}
class UserTwo extends Model {
    public function profile() {
        return $this->has_one('Profile', 'my_custom_fk_column');
    }
}
class ProfileTwo extends Model {
    public function user() {
        return $this->belongs_to('User', 'custom_user_fk_column');
    }
}
class Post extends Model { }
class UserThree extends Model {
    public function posts() {
        return $this->has_many('Post');
    }
}
class UserFour extends Model {
    public function posts() {
        return $this->has_many('Post', 'my_custom_fk_column');
    }
}

class Author extends Model { }
class AuthorBook extends Model { }
class Book extends Model {
    public function authors() {
        return $this->has_many_through('Author');
    }
}
class BookTwo extends Model {
    public function authors() {
        return $this->has_many_through('Author', 'AuthorBook', 'custom_book_id', 'custom_author_id');
    }
}
class MockPrefix_Simple extends Model { }
class MockPrefix_TableSpecified extends Model {
    public static $_table = 'simple';
}
