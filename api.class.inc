<?php

/**
 * Determine whether an API response is an error.
 * @param bool | Emfl_Response $response
 * @return bool
 */
function emfl_response_is_error( $response ) {
  if(empty( $response )) return FALSE;
  if( get_class( $response ) != 'Emfl_Response' ) return FALSE;
  if( $response->success == 0 ) return FALSE;
  return TRUE;
}

/**
 * A wrapper library for the emfluence Marketing Platform.
 * Function names correspond to API endpoints.
 * Refer to http://apidocs.emailer.emfluence.com
 * TODO: To make this CMS-independent, remove the Drupal functions from call()
 * @author jschwartz
 */
class Emfl_Platform_API {

  var $apikey;
  var $url = 'https://api.emailer.emfluence.com/v0';
  var $timeout = 15; // Low so that if the Platform API is slow, it doesn't cause this script to time out.
  var $error_function;

  /**
   * @param string $api_key
   * @param string $http_request_function Function name. The function gets passed the URL
   */
  function __construct( $api_key, $error_function = NULL ) {
    $this->apikey = $api_key;
    $this->error_function = $error_function;
  }

  /**
   * Use the error function passed in the constructor.
   * @param string $msg
   */
  private function err( $msg ) {
    $func = $this->error_function;
    if( !empty($func) && is_callable($func) ) $func( $msg );
  }

  /**
   * Make a request using the API
   *
   * Return value is FALSE if the request is blocked or unsuccessful in transmission.
   * In this case, the error function passed in the constructor is given an explanation.
   *
   * If communication with the Platform happened, return object is an Emfl_Response object.
   * Bad API calls that get a response from the Platform will have properties that can be
   * inspected like 'status', 'code' and 'errors'. If the response has a bad status,
   * the error function passed in the constructor is given an explanation.
   *
   * This is mostly an internal method, as successful response data should be
   * converted to an object of some type.
   *
   * @param string $endpoint The API endpoint, not beginning with a slash.
   * Eg, 'helper/ping' would be an endpoint.
   * @param array $params Per the API documentation.
   * @return boolean | Emfl_Response
   */
  function call( $endpoint, $params ) {

    // make the call with the best request handler available
    $url = $this->url . '/' . $endpoint;
    $params['apiKey'] = $this->apikey;

    if( function_exists('drupal_http_request') ) {
      // Drupal request handler
      $response = drupal_http_request(
          $url,
          array( 'Content-Type' => 'application/json' ),
          'POST',
          json_encode( (object) $params),
          1,
          15
      );

    } elseif( function_exists('wp_remote_post') && false ) {
      // Wordpress request handler
      $response = wp_remote_post(
      	$url,
        array(
          'timeout' => $this->timeout,
        	'headers' => array( array( 'Content-Type' => 'application/json' ) ),
          'body' => json_encode( (object) $params )
        )
      );    dbgout($response);

      if( !is_wp_error($response) ) {
        $response = (object) $response['response'];
        $response->data = $response->body;
      }

    } elseif( function_exists('curl_init') ) {
      // Fall back on CURL
      $curl = curl_init($url);
      curl_setopt($curl, CURLOPT_HEADER, false);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
      curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
      curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode( (object) $params ) );
      $json_response = curl_exec($curl);
      $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      curl_close($curl);
      $response = (object) array(
      	'data' => $json_response,
        'code' => $status
      );

    } else {
      $this->err( 'No request handler could be found. Please install CURL on your server.' );
      return FALSE;
    }

    // Look for transmission fail
    if( !isset($response->code) || ($response->code != 200) ) {
      $this->err( 'Transmission fail: ' . var_export($response, TRUE) );
      return FALSE;
    }

    // Focus on the response content
    require_once 'response_objects/wrapper.class.inc';
    $response = json_decode($response->data);
    $response = new Emfl_Response($response);

    // Look for API fail
    if($response->success != 1) {
      $this->err( 'Bad API call: ' . $response->code . ' - ' . var_export($response->errors, TRUE) );
    }

    return $response;
  }

  /**
   * Test method, use it to see whether API key,
   * error_function and request handlers are working.
   *
   * @see http://apidocs.emailer.emfluence.com/#responses
   * @return boolean | Emfl_Response
   */
  function ping() {
    return $this->call( 'helper/ping', array() );
  }

  /**
   * Save a contact and get the saved contact in return.
   *
   * The return value could be FALSE if a transmission error occurred,
   * like being blocked by the Platform or a network issue.
   *
   * Otherwise even bad API calls will get an Emfl_Response object
   * that corresponds with the Platform's response format. See here:
   * http://apidocs.emailer.emfluence.com/#responses
   *
   * Don't forget to check the 'success' property before assuming that
   * the operation occurred correctly. If an error occurred, the 'data'
   * property will be FALSE and the 'errors' property will have details.
   *
   * Finally, if the operation was successful, the 'data' property is a
   * populated Emfl_Contact object for the saved contact.
   *
   * @param array $params Per the API reference.
   * @return bool | Emfl_Response
   * @see http://apidocs.emailer.emfluence.com/endpoints/contacts/save
   */
  function contacts_save( $params ) {
    $response = $this->call('contacts/save', $params);
    if(empty($response)) return FALSE; // Transmission error
    require_once 'response_objects/contact.class.inc';
    if(!empty( $response->data )) $response->data = new Emfl_Contact($response->data);
    return $response;
  }

  /**
   * Import / save multiple contacts
   * 
   * The return value could be FALSE if a transmission error occurred,
   * like being blocked by the Platform or a network issue.
   *
   * Otherwise even bad API calls will get an Emfl_Response object
   * that corresponds with the Platform's response format. See here:
   * http://apidocs.emailer.emfluence.com/#responses
   *
   * Don't forget to check the 'success' property before assuming that
   * the operation occurred correctly. If an error occurred, the 'data'
   * property will be FALSE and the 'errors' property will have details.
   *
   * Finally, if the operation was successful, the 'data' property is a
   * populated Emfl_Contacts_Import object with summary details.
   *
   * @param array $params Per the API reference.
   * @return bool | Emfl_Response
   * @see http://apidocs.emailer.emfluence.com/endpoints/contacts/import
   */
  function contacts_import( $params ) {
    $response = $this->call('contacts/import', $params);
    if(empty($response)) return FALSE; // Transmission error
    require_once 'response_objects/contacts.import.class.inc';
    if(!empty( $response->data )) $response->data = new Emfl_Contacts_Import($response->data);
    return $response;
  }
  
}
