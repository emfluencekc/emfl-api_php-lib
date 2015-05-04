<?php 

class Emfl_Group {

  var $friendlyName;
  var $dateAdded;
  var $description;
  var $groupID;
  var $private;
  var $dateModified;
  var $groupName;
  var $autoResponseEmailID;
  var $userID;

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
