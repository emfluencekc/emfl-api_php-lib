<?php 

require_once 'generic.class.php';

class Emfl_EmailReport_View extends Emfl_Response_Generic {

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
  var $ipAddress;

  /**
   * @var string
   */
  var $date;

}
