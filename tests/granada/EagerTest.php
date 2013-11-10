<?php

use Granada\Orm;
use Granada\Model;

/**
 * Testing eager loading
 *
 * @author Peter Schumacher <peter@schumacher.dk>
 *
 * Modified by Tom van Oorschot <tomvanoorschot@gmail.com>
 * Additions:
 *  - Test will also check for double records on a has_many relation
 */
class EagerTest extends PHPUnit_Framework_TestCase {

    public function setUp() {

        // The tests for eager loading requires a real database.
        // Set up SQLite in memory
        ORM::set_db(new PDO('sqlite::memory:'));

        // Create schemas and populate with data
        ORM::get_db()->exec(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..'.DIRECTORY_SEPARATOR.'models.sql'));

        // Enable logging
        ORM::configure('logging', true);
    }

    public function tearDown() {
        ORM::configure('logging', false);
        ORM::set_db(null);
    }


    public function testFindOneWith1BelongsTo() {

        $car = Model::factory('Car')->with('manufactor')->find_one(1);

        $expectedSql   = array();
        $expectedSql[] = "SELECT * FROM `car` WHERE `id` = '1' LIMIT 1";
        $expectedSql[] = "SELECT * FROM `manufactor` WHERE `id` IN ('1')";

        $fullQueryLog = ORM::get_query_log();

        // Return last two queries
        $actualSql = array_slice($fullQueryLog, count($fullQueryLog) - 2);

        $this->assertEquals($expectedSql, $actualSql);

    }

    public function testFindOneWith2BelongsTo() {
        $car = Model::factory('Car')->with('owner','manufactor')->find_one(1);

        $expectedSql = array();
        $expectedSql[] = "SELECT * FROM `car` WHERE `id` = '1' LIMIT 1";
        $expectedSql[] = "SELECT * FROM `owner` WHERE `id` IN ('1')";
        $expectedSql[] = "SELECT * FROM `manufactor` WHERE `id` IN ('1')";

        $fullQueryLog = ORM::get_query_log();

        // Return last three queries
        $actualSql = array_slice($fullQueryLog, count($fullQueryLog) - 3);

        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testFindOneWith1HasOne() {
        $owner = Model::factory('Owner')->with('car')->find_one(1);

        $expectedSql   = array();
        $expectedSql[] = "SELECT * FROM `owner` WHERE `id` = '1' LIMIT 1";
        $expectedSql[] = "SELECT * FROM `car` WHERE `owner_id` IN ('1')";

        $fullQueryLog = ORM::get_query_log();

        // Return last two queries
        $actualSql = array_slice($fullQueryLog, count($fullQueryLog) - 2);

        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testFindOneWith1HasMany() {
        $manufactor = Model::factory('Manufactor')->with('cars')->find_one(1);

        $expectedSql   = array();
        $expectedSql[] = "SELECT * FROM `manufactor` WHERE `id` = '1' LIMIT 1";
        $expectedSql[] = "SELECT * FROM `car` WHERE `manufactor_id` IN ('1')";
        $fullQueryLog = ORM::get_query_log();

        // Return last two queries
        $actualSql = array_slice($fullQueryLog, count($fullQueryLog) - 2);

        $this->assertEquals($expectedSql, $actualSql);
    }

     public function testFindOneWithHasManyThrough() {
        $car = Model::factory('Car')->with('parts')->find_one(1);
        $actualParts = array();
        foreach($car->parts as $p) {
            $actualParts[] = $p->as_array();
        }

        $expectedSql    = array();
        $expectedSql[]  = "SELECT * FROM `car` WHERE `id` = '1' LIMIT 1";
        $expectedSql[]  = "SELECT `part`.*, `car_part`.`car_id` FROM `part` JOIN `car_part` ON `part`.`id` = `car_part`.`part_id` WHERE `car_part`.`car_id` IN ('1')";

        $expectedParts = array();
        $expectedParts[] = array('id' => 1, 'name' => 'Part1');
        $expectedParts[] = array('id' => 2, 'name' => 'Part2');
        $expectedParts[] = array('id' => 1, 'name' => 'Part1');

        $fullQueryLog = ORM::get_query_log();

        // Return last three queries
        $actualSql = array_slice($fullQueryLog, count($fullQueryLog) - 2);


        $this->assertEquals($expectedSql, $actualSql);
        $this->assertEquals($expectedParts, $actualParts);

    }

    public function testFindManyWith2BelongsTo() {
        $cars = Model::factory('Car')->with('owner','manufactor')->find_many();

        $expectedSql = array();
        $expectedSql[] = "SELECT * FROM `car`";
        $expectedSql[] = "SELECT * FROM `owner` WHERE `id` IN ('1', '2', '3', '4')";
        $expectedSql[] = "SELECT * FROM `manufactor` WHERE `id` IN ('1', '2')";

        $fullQueryLog = ORM::get_query_log();

        // Return last three queries
        $actualSql = array_slice($fullQueryLog, count($fullQueryLog) - 3);

        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testFindManyWith1HasOne() {
        $owner = Model::factory('Owner')->with('car')->find_many();

        $expectedSql   = array();
        $expectedSql[] = "SELECT * FROM `owner`";
        $expectedSql[] = "SELECT * FROM `car` WHERE `owner_id` IN ('1', '2', '3', '4')";

        $fullQueryLog = ORM::get_query_log();

        // Return last two queries
        $actualSql = array_slice($fullQueryLog, count($fullQueryLog) - 2);

        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testFindManyWith1HasMany() {
        $manufactor = Model::factory('Manufactor')->with('cars')->find_many();

        $expectedSql   = array();
        $expectedSql[] = "SELECT * FROM `manufactor`";
        $expectedSql[] = "SELECT * FROM `car` WHERE `manufactor_id` IN ('1', '2')";

        $fullQueryLog = ORM::get_query_log();

        // Return last two queries
        $actualSql = array_slice($fullQueryLog, count($fullQueryLog) - 2);

        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testFindManyWithHasManyThrough() {
        $parts = Model::factory('Part')->with('cars')->find_many();

        $actualParts = array();

        foreach($parts as $part) {
            $tmp = $part->as_array();
            $tmp['cars'] = array();

            if(count($part->cars) > 0) {
                foreach($part->cars as $car) {
                    $tmp['cars'][] = $car->as_array();

                }
            }

            $actualParts[] = $tmp;
        }

        $expectedParts = array();
        $expectedParts[] =  array('id' => '1', 'name' => 'Part1',
                                'cars' => array(
                                    array('id' => '1', 'name' => 'Car1', 'manufactor_id' => '1', 'owner_id' => '1'),
                                    array('id' => '2', 'name' => 'Car2', 'manufactor_id' => '1', 'owner_id' => '2'),
                                    array('id' => '3', 'name' => 'Car3', 'manufactor_id' => '2', 'owner_id' => '3'),
                                    array('id' => '4', 'name' => 'Car4', 'manufactor_id' => '2', 'owner_id' => '4'),
                                    array('id' => '1', 'name' => 'Car1', 'manufactor_id' => '1', 'owner_id' => '1'),
                                )
                            );

        $expectedParts[] =  array('id' => '2', 'name' => 'Part2',
                                'cars' => array(
                                    array('id' => '1', 'name' => 'Car1', 'manufactor_id' => '1', 'owner_id' => '1'),
                                )
                            );

        $expectedParts[] =  array('id' => '3', 'name' => 'Part3',
                                'cars' => array(
                                    array('id' => '2', 'name' => 'Car2', 'manufactor_id' => '1', 'owner_id' => '2'),
                                )
                            );

        $expectedParts[] =  array('id' =>  '4', 'name' => 'Part4',
                                'cars' => array(
                                    array('id' => '3', 'name' => 'Car3', 'manufactor_id' => '2', 'owner_id' => '3'),
                                )
                            );

        $expectedParts[] =  array('id' => '5', 'name' => 'Part5',
                                'cars' => array(
                                    array('id' => '4', 'name' => 'Car4', 'manufactor_id' => '2', 'owner_id' => '4'),
                                )
                            );

        $expectedSql    = array();
        $expectedSql[]  = "SELECT * FROM `part`";
        $expectedSql[]  = "SELECT `car`.*, `car_part`.`part_id` FROM `car` JOIN `car_part` ON `car`.`id` = `car_part`.`car_id` WHERE `car_part`.`part_id` IN ('1', '2', '3', '4', '5')";

        $fullQueryLog = ORM::get_query_log();

        // Return last two queries
        $actualSql = array_slice($fullQueryLog, count($fullQueryLog) - 2);

        $this->assertEquals($expectedSql, $actualSql);
        $this->assertEquals($expectedParts, $actualParts);

    }

    public function testChainedRelationships() {
        $owner = Owner::with(array('car'=>array('with'=>'manufactor')))->find_one(1);
        $fullQueryLog = ORM::get_query_log();
        // Return last three queries
        $actualSql = array_slice($fullQueryLog, count($fullQueryLog) - 3);

        $expectedSql    = array();
        $expectedSql[]  = "SELECT * FROM `owner` WHERE `id` = '1' LIMIT 1";
        $expectedSql[]  = "SELECT * FROM `car` WHERE `owner_id` IN ('1')";
        $expectedSql[]  = "SELECT * FROM `manufactor` WHERE `id` IN ('1')";

        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testChainedAdterHas_Many_Through() {
        $car = Car::with(array('parts'=>array('with'=>'cars')))->find_one(1);
        $test_exists = $car->as_array();
        $test_exists = $car->parts->as_array();
        foreach($car->parts as $part){
            foreach($part->cars as $car){
                $test_exists = $car->as_array();
            };
        }
        // NO FATAL ERRORS OR EXCEPTIONS THROW
        $this->assertInstanceOf('Granada\Model', $car);
    }

    public function testLazyLoading() {
        $owner = Model::factory('Owner')->find_one(1);
        $this->assertEquals($owner->car->manufactor_id, 1);
    }
}
