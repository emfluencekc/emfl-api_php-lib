<?php 

class Emfl_Groups_Search {
  
  /**
   * @var int[]
   */
  var $paging = array();
  
  /**
   * @var Emfl_Email[]
   */
  var $records = array();
  
  /**
   * @param stdClass $paging
   * @param stdClass $records
   */
  function __construct( $paging, $records ) {
    $this->paging = (array) $paging;
    require_once 'group.class.inc';
    foreach( $records as $el ) {
      $this->records[] = new Emfl_Group($el);
    }
  }
  
}
