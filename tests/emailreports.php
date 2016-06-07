<?php

function emfl_api_emailreports_tests() {
  $api = emfl_api_get_instance();

  $emails = $api->emails_search();
  $test_email = $emails->data->records[0];

  // emailReports/Views
  $successes = array();
  $failures = array();

  $response = $api->emailreports_views(array('emailID' => $test_email->emailID));
  $failures = $api->errors->get(TRUE);
  if(!emfl_response_is_error($response) && (TRUE == $response->success) && !empty($response->data)) {
    $successes[] = 'Found views report for email with ID of: ' . $test_email->emailID;

    if ($response->data->records !== NULL) {
      $successes[] = 'Views correctly returned';
    }
    else {
      $failures[] = 'Views not correctly returned';
    }
  }
  else {
    $failures[] = 'Could not find views report for email with ID of: ' . $test_email->emailID;
  }
  
  emfl_api_test_output_results('emailReports/Views', $successes, $failures);
  
  // emailReports/Clicks
  $successes = array();
  $failures = array();

  $response = $api->emailreports_clicks(array('emailID' => $test_email->emailID));
  $failures = $api->errors->get(TRUE);
  if(!emfl_response_is_error($response) && (TRUE == $response->success) && !empty($response->data)) {
    $successes[] = 'Found clicks report for email with ID of: ' . $test_email->emailID;

    if ($response->data->records !== NULL) {
      $successes[] = 'Clicks correctly returned';
    }
    else {
      $failures[] = 'Clicks not correctly returned';
    }
  }
  else {
    $failures[] = 'Could not find clicks report for email with ID of: ' . $test_email->emailID;
  }
  
  emfl_api_test_output_results('emailReports/Clicks', $successes, $failures);
}

emfl_api_emailreports_tests();
