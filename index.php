<?php
class Beverage {
  public $temperature;
  
  function getInfo() {
    return "This beverage is $this->temperature.";
  }
}

class Milk extends Beverage {
  function __construct() {
    $this->temperature = "hot";
  }
// Overriding function
  function  newInfo(){
    return parent::getInfo() . " I like my milk $this->temperature.";
  }
  
}
$milk = new Milk();
echo $milk->newInfo();
