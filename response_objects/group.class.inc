<?php 

class Emfl_Group {

  var $userID;
  var $groupID;
  var $groupName;
  var $friendlyName;
  
  var $dateAdded;
  var $dateModified;
  var $dateLastEmailSent;
  
  var $description;
  var $private;
  var $status;
  var $autoResponseEmailID;
  var $totalMembers;
  var $activeMembers;

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
