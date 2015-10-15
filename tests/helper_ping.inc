<?php 

function emfl_api_test_ping() {
  $api = emfl_api_get_instance();
  $successes = array();
  $failures = array();
  
  $ping = $api->ping();
  if(!emfl_response_is_error($ping) && (TRUE == $ping->success)) $successes[] = 'Pong';
  $failures = $api->errors->get(TRUE);
  
  emfl_api_test_output_results('Ping', $successes, $failures);
}

emfl_api_test_ping();
