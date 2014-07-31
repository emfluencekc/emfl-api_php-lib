<?php 

define( 'EMFL_PLATFORM_API_KEY', '123abc' );

/**
 * Get a singleton API library instance
 * @return Emfl_Platform_API
 */
function emfl_get_api_instace() {
  static $wrapper = NULL;
  if(!empty($wrapper)) return $wrapper;

  require_once('api.class.inc');
  $wrapper = new Emfl_Platform_API( EMFL_PLATFORM_API_KEY, 'emfl_api_error' );
  return $wrapper;
}

/**
 * Callback function if an API error occurs.
 * Output to page.
 * @param string $msg
 */
function emfl_api_error( $msg ) {
  echo '<h3>ERROR</h3><pre>' . $msg . '</pre>';
}


/*
 * Here we go! Do a test ping and outupt the result.
 */
$emfl = emfl_get_api_instace();
$result = $emfl->ping();
echo '<h1>RESULT</h1><pre>' . var_export($result, TRUE) . '</pre>';
