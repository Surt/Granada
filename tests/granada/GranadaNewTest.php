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
class GranadaNewTest extends PHPUnit_Framework_TestCase {

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

    public function testGetter(){
        $car = Model::factory('Car')->find_one(1);
        $expected = 'Car1';
        $this->assertEquals($expected, $car->get('name'), 'Get method test');
        $this->assertEquals($expected, $car->name, '__get magic method test');

    	$car = Model::factory('Car')->find_one(1);
    	$expected = null;
        $this->assertEquals($expected, $car->nonExistentProperty, 'NULL returned if no property found');

        $car = Model::factory('Car')->find_one(1);
        $expected = 'test test';
        $car->existingProperty = 'TEST TeSt';
        $this->assertEquals($expected, $car->existingProperty, 'get_ method overload test');

        $car = Model::factory('Car')->find_one(1);
        $expected = 'This property is missing';
        $this->assertEquals($expected, $car->someProperty, 'Missing property fallback test');
    }

    public function testSetterForProperty(){
    	$car = Model::factory('Car')->find_one(1);
    	$car->name = 'Car1';
    	$car->save();
    	$expected = 'test';
        $this->assertEquals($expected, $car->name);
    }

    public function testSetterForRelationship(){
    	$car = Model::factory('Car')->with('manufactor')->find_one(1);
    	$expected = 'Manufactor1';
        $this->assertEquals($expected, $car->manufactor->name, 'Relationship loaded');

    	$expected = 'test';
        $car->manufactor = 'test';

        $this->assertEquals($expected, $car->relationships['manufactor'], 'Relationship overloaded');
    }

    public function testCallStaticForModel(){
    	$expected  = Model::factory('Car')->with('manufactor')->find_one(1);
		$car       = Car::with('manufactor')->find_one(1);
        $this->assertEquals($expected, $car, 'Call from static and from factory are the same');
    }

    public function testPluckValid(){
        $id = Car::where_id_is(1)->pluck('id');
        $this->assertEquals(1, $id, 'PLuck a column');
    }

    public function testPluckInvalid(){
        $id = Car::where_id_is(10)->pluck('id');
        $this->assertNull($id);
    }

    public function testfindPairs(){
        $pairs = Car::find_pairs('id', 'name');
        $expected = array(
            '1' => 'Car1',
            '2' => 'Car2',
            '3' => 'Car3',
            '4' => 'Car4'
        );
        $this->assertEquals($expected, $pairs);
    }

    public function testNoResultsfindPairs(){
        $pairs = Car::where('id',10)->find_pairs('id', 'name');
        $this->assertNull($pairs);
    }

    public function testfilters(){
        $car = Car::byName('Car1')->find_one();
        $this->assertEquals($car->name, 'Car1');
    }

    /**
     * @expectedException Exception
     */
    public function testnonExistentFilter(){
        $car = Car::test('Car1')->find_one();
    }

    public function testInsert(){
        Car::insert(array(
            array(
                'id'=> '20',
                'name' =>'Car20',
                'manufactor_id'=>  1,
                'owner_id'=>  1
            )
        ));
        $count = Car::count();
        $this->assertEquals(5, $count, 'Car must be Inserted');
    }
}
