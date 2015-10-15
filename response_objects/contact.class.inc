<?php 

require_once 'generic.class.inc';

class Emfl_Contact extends Emfl_Response_Generic {

  var $contactID;
  var $customerID; 
  var $userID;

  var $title;
  var $company;
  var $email; 
  var $phone;
  var $fax; 
  var $firstName; 
  var $lastName;
  var $dateOfBirth; 
  
  var $address1; 
  var $address2; 
  var $city; 
  var $state; 
  var $zipCode;
  var $country; 
  
  /**
   * @var array[]
   */
  var $contentVariables;
  
  /**
   * @var array[]
   */
  var $customFields;
  
  /**
   * @var int[]
   */
  var $groupIDs;
  
  var $dateAdded; 
  var $dateHeld; 
  var $dateModified; 
  var $dateSuppressed; 
  var $held; 
  var $ipAddress;
  var $memo; 
  var $notes; 
  var $originalSource; 
  var $purl; 
  var $suppressed; 
  
}
