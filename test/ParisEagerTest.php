<?php

class ParisEagerTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        // Set up the dummy database connection
        ORM::set_db(new MockPDO('sqlite::memory:'));

        // Enable logging
        ORM::configure('logging', true);
    }

    public function tearDown() {
        ORM::configure('logging', false);
        ORM::set_db(null);
    }

    public function testFindOneWithOneRelation() {
        $user = Model::factory('Car')->with('manufactor')->find_one(1);
        
        $expected = array();
        $expected[] = "SELECT * FROM `car` WHERE `id` = '1' LIMIT 1";
        $expected[] = "SELECT * FROM `manufactor` WHERE `car_id` IN ('1') GROUP BY `car_id`";

        $fullQueryLog = ORM::get_query_log();

        // Return last two queries
        $actual = array_slice($fullQueryLog, count($fullQueryLog) - 2);
        //print_r($actual);

        $this->assertEquals($expected, $actual);
    }

    public function testFindOneWithTwoRelations() {
        $user = Model::factory('Car')->with('parts','manufactor')->find_one(1);
        
        $expected = array();
        $expected[] = "SELECT * FROM `car` WHERE `id` = '1' LIMIT 1";
        $expected[] = "SELECT * FROM `part` WHERE `car_id` IN ('1')";
        $expected[] = "SELECT * FROM `manufactor` WHERE `car_id` IN ('1', '') GROUP BY `car_id`";
        
        $fullQueryLog = ORM::get_query_log();

        // Return last three queries
        $actual = array_slice($fullQueryLog, count($fullQueryLog) - 3);
 
        $this->assertEquals($expected, $actual);
    }

    /*
    public function testHasOneWithCustomForeignKeyName() {
        $user2 = Model::factory('UserTwo')->find_one(1);
        $profile = $user2->profile()->find_one();
        $expected = "SELECT * FROM `profile` WHERE `my_custom_fk_column` = '1' LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testBelongsToRelation() {
        $user2 = Model::factory('UserTwo')->find_one(1);
        $profile = $user2->profile()->find_one();
        $profile->user_id = 1;
        $user3 = $profile->user()->find_one();
        $expected = "SELECT * FROM `user` WHERE `id` = '1' LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testBelongsToRelationWithCustomForeignKeyName() {
        $profile2 = Model::factory('ProfileTwo')->find_one(1);
        $profile2->custom_user_fk_column = 5;
        $user4 = $profile2->user()->find_one();
        $expected = "SELECT * FROM `user` WHERE `id` = '5' LIMIT 1";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testHasManyRelation() {
        $user4 = Model::factory('UserThree')->find_one(1);
        $posts = $user4->posts()->find_many();
        $expected = "SELECT * FROM `post` WHERE `user_three_id` = '1'";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testHasManyRelationWithCustomForeignKeyName() {
        $user5 = Model::factory('UserFour')->find_one(1);
        $posts = $user5->posts()->find_many();
        $expected = "SELECT * FROM `post` WHERE `my_custom_fk_column` = '1'";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testHasManyThroughRelation() {
        $book = Model::factory('Book')->find_one(1);
        $authors = $book->authors()->find_many();
        $expected = "SELECT `author`.* FROM `author` JOIN `author_book` ON `author`.`id` = `author_book`.`author_id` WHERE `author_book`.`book_id` = '1'";
        $this->assertEquals($expected, ORM::get_last_query());
    }

    public function testHasManyThroughRelationWithCustomIntermediateModelAndKeyNames() {
        $book2 = Model::factory('BookTwo')->find_one(1);
        $authors2 = $book2->authors()->find_many();
        $expected = "SELECT `author`.* FROM `author` JOIN `author_book` ON `author`.`id` = `author_book`.`custom_author_id` WHERE `author_book`.`custom_book_id` = '1'";
        $this->assertEquals($expected, ORM::get_last_query());
    }
    */

}