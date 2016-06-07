<?php 

/**
 * Generic wrapper for the 'data' component in responses.
 * Specific data classes should extend this.
 */
class Emfl_Response_Generic {

  /**
   * @param array | stdClass $data Containing the properties to set
   */
  function __construct( $data = array() ) {
    $data = (array) $data;
    foreach( $data as $key=>$val ) {
      $this->$key = $val;
      if(is_object($val)) $this->$key = $this->recursive_cast($val);
    }
  }
  
  /**
   * Recursively cast any objects to arrays. 
   * JSON favors objects but it's better to be consistent in 
   * PHP and arrays cover everything objects do. 
   * Plus, var_export works better on arrays.
   * @param mixed | array | object $val
   * @return mixed | array
   */
  protected function recursive_cast($val) {
    if(!is_object($val)) return $val;
    $val = (array) $val;
    foreach($val as $subkey=>$subval) $val[$subkey] = $this->recursive_cast($subval);
    return $val;
  }
  
}
