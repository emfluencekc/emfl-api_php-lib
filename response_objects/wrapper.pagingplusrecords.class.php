<?php

class Emfl_PagingPlusRecords {

  /**
   * @var int[]
   */
  var $paging = array();

  /**
   * @var Emfl_Group[]
   */
  var $records = array();

  /**
   * @param stdClass $paging
   * @param stdClass $records
   * @param string $record_class The class to use for the record items.
   *  Ensure that the class file is already included.
   */
  function __construct( $paging, $records, $record_class ) {
    $this->paging = (array) $paging;
    foreach( $records as $el ) {
      $this->records[] = new $record_class($el);
    }
  }

}
