<?php 

class Emfl_Contact {

  var $address1,
  $address2,
  $city,
  $company,
  $contactID,
  $content1,
  $content2,
  $content3,
  $country,
  $custom1,
  $custom10,
  $custom11,
  $custom12,
  $custom13,
  $custom14,
  $custom15,
  $custom16,
  $custom17,
  $custom18,
  $custom19,
  $custom2,
  $custom20,
  $custom3,
  $custom4,
  $custom5,
  $custom6,
  $custom7,
  $custom8,
  $custom9,
  $customerID,
  $dateAdded,
  $dateHeld,
  $dateModified,
  $dateOfBirth,
  $dateSuppressed,
  $email,
  $fax,
  $firstName,
  $groupIDs,
  $held,
  $ipAddress,
  $lastName,
  $memo,
  $notes,
  $originalSource,
  $phone,
  $purl,
  $state,
  $suppressed,
  $title,
  $userID,
  $zipCode;
  
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
