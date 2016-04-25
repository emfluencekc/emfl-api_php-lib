<?php 

require_once 'generic.class.inc';

class Emfl_EmailReport_Click extends Emfl_Response_Generic {

  /**
   * @var int
   */
  var $contactID;

  /**
   * @var string
   */
  var $email;

  /**
   * @var string
   */
  var $link;

  /**
   * @var string
   */
  var $ipAddress;

  /**
   * @var string
   */
  var $date;

}
