<?php 

class Emfl_Contacts_Import {

  var $recordsUpdated, $recordsInserted, $recordsInData;
  
  /**
   * @param array | stdClass $data Containing the properties to set
   */
  function __construct( $data ) {
    $data = (array) $data;
    foreach( $data as $key=>$val ) {
      $this->$key = $val;
    }
  }

}
