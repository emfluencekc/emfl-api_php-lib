<?php 

/**
 * Refer to http://apidocs.emailer.emfluence.com/#responses
 * @author jschwartz
 */
class Emfl_Response {
  
  /**
   * @var int
   */
  var $success;
  
  /**
   * @see http://apidocs.emailer.emfluence.com/#handling-errors
   * @var int
   */
  var $code;
  
  /**
   * Successful operations should cause this to be filled with an object of some type.
   * @var Emfl_Response_Generic | Emfl_Contact | Emfl_Contacts_Import | Emfl_Email | mixed | FALSE
   */
  var $data;
  
  /**
   * @see http://apidocs.emailer.emfluence.com/#handling-errors
   * @var array
   */
  var $errors;
  
  /**
   * @param array | stdClass $data Containing the properties to set
   */
  function __construct( $data ) {
    $data = (array) $data;
    foreach( $data as $key=>$val ) {
      $this->$key = $val;
    }
    
    // convert an empty stdClass object to FALSE so empty() can be used against it.
    if(is_object($this->data)) {
      $data_prop = (array) $this->data;
      if(empty( $data_prop )) $this->data = FALSE;
    }
    
  }
  
}