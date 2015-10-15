<?php 

function emfl_api_test_contacts() {

  $test_new_contact = array(
      'email' => 'test_' . time() . '@test.com',
      'firstName' => 'Testy',
      'lastName' => 'McTesterstein',
      'customFields' => array('custom1' => array('value' => 'abc'))
    );
  
  
  
  /* SAVE */
  
  $api = emfl_api_get_instance();
  $successes = array();
  $failures = array();
  
  $response = $api->contacts_save($test_new_contact);
  $failures = $api->errors->get(TRUE);
  if(!emfl_response_is_error($response) && (TRUE == $response->success)) {
    $successes[] = 'Saved ' . $test_new_contact['email'];
    foreach($test_new_contact as $fieldname=>$fieldval) {
      if($fieldname == 'customFields') {
        foreach($test_new_contact['customFields'] as $custom_name=>$custom_item) {
          if($response->data->customFields[$custom_name]['value'] == $fieldval[$custom_name]['value']) {
            $successes[] = 'custom field ' . $custom_name . ' correctly returned.';
          } else $failures[] = 'custom field ' . $custom_name . ' not correctly returned.';
        }
      } elseif($response->data->$fieldname == $fieldval) {
        $successes[] = $fieldname . ' correctly returned.';
      } else $failures[] = $fieldname . ' not correctly returned.';
    }
  }
  
  emfl_api_test_output_results('Contacts/Save', $successes, $failures);

  
  
  /* LOOKUP */
  
  $api = emfl_api_get_instance();
  $successes = array();
  $failures = array();
  
  $response = $api->contacts_lookup(array('email' => $test_new_contact['email']));
  $failures = $api->errors->get(TRUE);
  if(!emfl_response_is_error($response) && (TRUE == $response->success) && !empty($response->data)) {
    $successes[] = 'Found ' . $test_new_contact['email'];
    foreach($test_new_contact as $fieldname=>$fieldval) {
      if($fieldname == 'customFields') {
        foreach($test_new_contact['customFields'] as $custom_name=>$custom_item) {
          if($response->data->customFields[$custom_name]['value'] == $fieldval[$custom_name]['value']) {
            $successes[] = 'custom field ' . $custom_name . ' correctly returned.';
          } else $failures[] = 'custom field ' . $custom_name . ' not correctly returned.';
        }
      } elseif($response->data->$fieldname == $fieldval) {
        $successes[] = $fieldname . ' correctly returned.';
      } else $failures[] = $fieldname . ' not correctly returned.';
    }
  }
  
  emfl_api_test_output_results('Contacts/Lookup', $successes, $failures);
  
  
  
  /* DELETE */
  
  $api = emfl_api_get_instance();
  $successes = array();
  $failures = array();
  
  $response = $api->contacts_delete($response->data->contactID);
  $failures = $api->errors->get(TRUE);
  if(!emfl_response_is_error($response) && (TRUE == $response->success)) {
    $successes[] = 'Deleted ' . $test_new_contact['email'];
  }
  
  emfl_api_test_output_results('Contacts/Delete', $successes, $failures);
  
}

emfl_api_test_contacts();
