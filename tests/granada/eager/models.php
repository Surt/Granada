<?php

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
 }

class CarPart extends Model { }