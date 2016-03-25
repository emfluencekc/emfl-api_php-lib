<?php 

require_once 'generic.class.inc';

class Emfl_EmailReport_Recipient extends Emfl_Response_Generic {

  /**
   * @var int
   */
  var $contactID;

  /**
   * @var string
   */
  var $firstName;

  /**
   * @var string
   */
  var $lastName;

  /**
   * @var string
   */
  var $email;


  /**
   * @var bool
   */
  var $complained;

  /**
   * @var bool
   */
  var $suppressed;

  /**
   * @var bool
   */
  var $bounceType;

  /**
   * @var bool
   */
  var $viewed;

  /**
   * @var bool
   */
  var $bounced;

  /**
   * @var bool
   */
  var $clicked;
  
}
