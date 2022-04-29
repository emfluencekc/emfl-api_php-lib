<?php

/**
 * Determine whether an API response is an error.
 * If this function returns TRUE, you have an error!
 * @param bool | Emfl_Response $response
 * @return bool
 */
function emfl_response_is_error( $response ) {
  if(empty( $response )) return TRUE;
  if( get_class( $response ) != 'Emfl_Response' ) return TRUE;
  if( $response->success == 0 ) return TRUE;
  return FALSE;
}

/**
 * A wrapper library for the emfluence Marketing Platform.
 * Function names correspond to API endpoints.
 * Refer to http://apidocs.emailer.emfluence.com
 * TODO: To make this CMS-independent, remove the Drupal functions from call()
 * @author jschwartz
 */
class Emfl_Platform_API {

  CONST API_URL = 'https://api.emailer.emfluence.com/v1';

  private $access_token; // Private so that it can't be changed after instantiation.
  public $timeout = 15; // Low so that if the Platform API is slow, it doesn't cause this script to time out.
  
  /**
   * The function or method to call when an error occurs.
   * You can either pass the string function name or 
   * an array containing the object and method name to be called:
   * for example, array($obj, $methodname)
   * @var string | array
   */
  var $error_function;

  /**
   * Provides an internal storage for errors produced by this api.
   * This will be used if an error function is not provided.
   * @var Emfl_Platform_API_Errors
   */
  var $errors = NULL;

  /**
   * @param string $access_token
   * @param string $error_function Function name. 
   * The function gets passed any error message as a string parameter.
   * @throws Exception If the access token is empty.
   */
  function __construct( $access_token, $error_function = NULL ) {
    if(empty($access_token)) throw new Exception('Emfluence API library instantiated with an empty access token.');
    $this->access_token = $access_token;

    $this->errors = new Emfl_Platform_API_Errors();
    if( !empty($error_function) ) {
      $this->error_function = $error_function;
    }
    
    // Wordpress VIP hosting requirement:
    if(defined( 'WPCOM_IS_VIP_ENV' )) $this->timeout = 3;
  }

  /**
   * Use the error function or method passed in the constructor.
   * @param string $msg
   */
  private function err( $msg ) {
    $this->errors->add($msg);
    $func = $this->error_function;
    if( empty($func) || !is_callable($func) ) return; 
    if( is_string($func) ) {
      $func( $msg );
    } elseif( is_array( $func ) ) {
      $obj = $func[0];
      $method = $func[1];
      $obj->$method($msg);
    }
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
    $url = self::API_URL . '/' . $endpoint;

    if( function_exists('drupal_http_request') && defined('VERSION') ) {
      require_once 'request/drupal.php';
      $response = emfl_platform_api_drupal_request($url, $params, $this->timeout, $this->access_token);
    } elseif( function_exists('wp_remote_post') ) {
      require_once 'request/wordpress.php';
      $response = emfl_platform_api_wordpress_request($url, $params, $endpoint, $this->timeout, $this->access_token);
      if(is_string($response)) {
        $this->err($response);
        return FALSE;
      }
    } elseif( function_exists('curl_init') ) {
      require_once 'request/generic.php';
      $response = emfl_platform_api_generic_request($url, $params, $this->timeout, $this->access_token);
    } else {
      $this->err( 'No request handler could be found. Please install CURL on your server.' );
      return FALSE;
    }

    // Look for transmission fail
    if( !isset($response->code) || ($response->code != 200) ) {
      $this->err( $endpoint . ': Transmission failure ' . var_export($response, TRUE) );
      return FALSE;
    }

    // Focus on the response content
    require_once 'response_objects/wrapper.class.php';
    $response = json_decode($response->data);
    $response = new Emfl_Response($response);

    // Look for API fail
    if($response->success != 1) {
      $this->err( 'Bad API call to ' . $endpoint . ': ' . $response->code . ' - ' . var_export($response->errors, TRUE) );
    }

    return $response;
  }

  
  //////////
  // Test //
  //////////
  
  /**
   * Test method, use it to see whether API token,
   * error_function and request handlers are working.
   *
   * @see http://apidocs.emailer.emfluence.com/#responses
   * @return boolean | Emfl_Response
   */
  function ping() {
    return $this->call( 'helper/ping', array() );
  }

  /**
   * Get a stub response: The structure of a response with blank data.
   * Useful in unit testing.
   * @param string $endpoint The API endpoint, not beginning with a slash.
   * Eg, 'helper/ping' would be an endpoint.
   * @return Emfl_Response
   */
  function call_stub($endpoint) {
    require_once 'response_objects/wrapper.class.php';
    $response = new Emfl_Response((object) array(
        'data' => '',
        'code' => ''
    ));
    switch($endpoint) {
      case 'contacts/lookup':
        require_once 'response_objects/contact.class.php';
        $response->data = new Emfl_Contact();
        break;
      case 'contacts/search':
        require_once 'response_objects/contact.class.php';
        $response->data = new Emfl_PagingPlusRecords(
            (object) array(
                "next" => NULL,
                "page" => NULL,
                "rpp" => NULL,
                "totalPages" => NULL,
                "totalRecords" => NULL,
                "prev" => NULL
            ),
            array(),
            'Emfl_Contact');
        break;
      case 'groups/search':
        require_once 'response_objects/wrapper.pagingplusrecords.class.php';
        require_once 'response_objects/group.class.php';
        $response->data = new Emfl_PagingPlusRecords(
            (object) array(
                "next" => NULL,
                "page" => NULL,
                "rpp" => NULL,
                "totalPages" => NULL,
                "totalRecords" => NULL,
                "prev" => NULL
            ),
            array(),
            'Emfl_Group');
        break;
      case 'emails/search':
        require_once 'response_objects/wrapper.pagingplusrecords.class.php';
        require_once 'response_objects/email.class.php';
        $response->data = new Emfl_PagingPlusRecords(
            (object) array(
                "next" => NULL,
                "page" => NULL,
                "rpp" => NULL,
                "totalPages" => NULL,
                "totalRecords" => NULL,
                "prev" => NULL
            ),
            array(),
            'Emfl_Email'
          );
        break;
      case 'emails/lookup':
        require_once 'response_objects/email.class.php';
        $response->data = new Emfl_Email();
        break;
      case 'emailReports/recipients':
        require_once 'response_objects/wrapper.pagingplusrecords.class.php';
        require_once 'response_objects/emailreports.recipient.class.php';
        $response->data = new Emfl_PagingPlusRecords(
            (object) array(
                "next" => NULL,
                "page" => NULL,
                "rpp" => NULL,
                "totalPages" => NULL,
                "totalRecords" => NULL,
                "prev" => NULL
            ),
            array(),
            'Emfl_EmailReport_Recipient'
        );
        break;
      case 'emailReports/views':
        require_once 'response_objects/wrapper.pagingplusrecords.class.php';
        require_once 'response_objects/emailreports.view.class.php';
        $response->data = new Emfl_PagingPlusRecords(
            (object) array(
                "next" => NULL,
                "page" => NULL,
                "rpp" => NULL,
                "totalPages" => NULL,
                "totalRecords" => NULL,
                "prev" => NULL
            ),
            array(),
            'Emfl_EmailReport_View'
        );
        break;
      case 'emailReports/clicks':
        require_once 'response_objects/wrapper.pagingplusrecords.class.php';
        require_once 'response_objects/emailreports.click.class.php';
        $response->data = new Emfl_PagingPlusRecords(
            (object) array(
                "next" => NULL,
                "page" => NULL,
                "rpp" => NULL,
                "totalPages" => NULL,
                "totalRecords" => NULL,
                "prev" => NULL
            ),
            array(),
            'Emfl_EmailReport_Click'
        );
        break;
    }
    return $response;
  }

  
  //////////////
  // Contacts //
  //////////////
  
  /**
   * Delete an existing contact record. Suppressed records cannot be deleted.
   *
   * The return value could be FALSE if a transmission error occurred,
   * like being blocked by the Platform or a network issue.
   *
   * Otherwise even bad API calls will get an Emfl_Response object
   * that corresponds with the Platform's response format. See here:
   * http://apidocs.emailer.emfluence.com/#responses
   *
   * Don't forget to check the 'success' property before assuming that
   * the operation occurred correctly. If an error occurred, the 'errors' 
   * property will have details.
   *
   * @param int $contactID Per the API reference.
   * @return bool | Emfl_Response
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/contacts/delete
   */
  function contacts_delete( $contactID ) {
    $response = $this->call('contacts/delete', array( 'contactID' => $contactID ));
    if(empty($response)) return FALSE; // Transmission error
    return $response;
  }
  
  /**
   * Get full details for contacts by either contactID or email
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
   * Finally, if the operation was successful, the 'data' property is 
   * either a populated Emfl_Contact object or an array of them.
   *
   * @param array $params Per the API reference.
   * @return bool | Emfl_Response
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/contacts/lookup
   */
  function contacts_lookup( $params ) {
    $response = $this->call('contacts/lookup', $params);
    if(empty($response)) return FALSE; // Transmission error
    require_once 'response_objects/contact.class.php';
    if(!empty( $response->data )) {
      if(is_array($response->data)) {
        foreach($response->data as &$record) $record = new Emfl_Contact($record);
      } else $response->data = new Emfl_Contact($response->data);
    }
    return $response;
  }
  
  /**
   * Search for contacts using various parameters
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
   * Finally, if the operation was successful, the 'data' property is 
   * either a populated Emfl_Contact object or an array of them.
   *
   * @param array $params Per the API reference.
   * @return bool | Emfl_Response
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/contacts/search
   */
  function contacts_search( $params ) {
    $response = $this->call('contacts/search', $params);
    if(empty($response)) return FALSE; // Transmission error
    if(!empty($response->data)) {
      require_once 'response_objects/wrapper.pagingplusrecords.class.php';
      require_once 'response_objects/contact.class.php';
      $response->data = new Emfl_PagingPlusRecords(
          $response->data->paging,
          $response->data->records,
          'Emfl_Contact');
    }
    return $response;
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
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/contacts/save
   */
  function contacts_save( $params ) {
    $response = $this->call('contacts/save', $params);
    if(empty($response)) return FALSE; // Transmission error
    require_once 'response_objects/contact.class.php';
    if(!empty( $response->data )) $response->data = new Emfl_Contact($response->data);
    return $response;
  }

  /**
   * Remove a contact from groups
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
   * populated Emfl_Response object specifying the details
   *
   * @param array $params Per the API reference.
   * @return bool | Emfl_Response
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/contacts/deleteGroups
   */
  function contacts_delete_groups( $params ) {
    $response = $this->call('contacts/deleteGroups', $params);
    if(empty($response)) return FALSE; // Transmission error
    require_once 'response_objects/generic.class.php';
    if(!empty( $response->data )) $response->data = new Emfl_Response($response->data);
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
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/contacts/import
   */
  function contacts_import( $params ) {
    $response = $this->call('contacts/import', $params);
    if(empty($response)) return FALSE; // Transmission error
    require_once 'response_objects/contacts.import.class.php';
    if(!empty( $response->data )) $response->data = new Emfl_Contacts_Import($response->data);
    return $response;
  }


  ////////////
  // Groups //
  ////////////

  /**
   * Groups / search
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
   * populated Emfl_PagingPlusRecords object with paging details and a records
   * list of Emfl_Group groups. Note however that the populated group objects
   * are not complete group objects, as per the API documentation.
   *
   * @param array $params Keyed values, per API documentation
   * @return false|Emfl_Response
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/groups/search
   */
  function groups_search( $params = array() ) {
    $response = $this->call('groups/search', $params);
    if(empty($response)) return FALSE; // Transmission error
    if(!empty($response->data)) {
      require_once 'response_objects/wrapper.pagingplusrecords.class.php';
      require_once 'response_objects/group.class.php';
      $response->data = new Emfl_PagingPlusRecords(
          $response->data->paging,
          $response->data->records,
          'Emfl_Group');
    }
    return $response;
  }


  ////////////
  // Emails //
  ////////////
  
  /**
   * Emails / search
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
   * populated Emfl_PagingPlusRecords object with paging details and a records
   * list of Emfl_Email emails. Note however that the populated email objects
   * are not complete email objects, as per the API documentation.
   *
   * @param array $params Keyed values, per API documentation
   * @return false|Emfl_Response
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/emails/copy
   */
  function emails_search( $params = array() ) {
    $response = $this->call('emails/search', $params);
    if(empty($response)) return FALSE; // Transmission error
    if(!empty($response->data)) {
      require_once 'response_objects/wrapper.pagingplusrecords.class.php';
      require_once 'response_objects/email.class.php';
      $response->data = new Emfl_PagingPlusRecords(
          $response->data->paging,
          $response->data->records,
          'Emfl_Email'
        );
    }
    return $response;
  }
  
  /**
   * Emails / lookup
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
   * populated Emfl_Email object with email details.
   * 
   * @param int $email_id The email ID
   * @return false|Emfl_Response
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/emails/lookup
   */
  function emails_lookup( $email_id ) {
    $response = $this->call('emails/lookup', array('emailID' => $email_id));
    if(empty($response)) return FALSE; // Transmission error
    require_once 'response_objects/email.class.php';
    if(!empty( $response->data )) $response->data = new Emfl_Email($response->data);
    return $response;
  }
  
  /**
   * Emails / save
   * 
   * Note that this method does NOT save all possible fields of an email object.
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
   * populated Emfl_Email object with email details.
   * 
   * @param Emfl_Email $email It's probably best to use a response object from an emails_lookup call as the input here.
   * @return false|Emfl_Response
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/emails/save
   */
  function emails_save( Emfl_Email $email ) {
    $response = $this->call('emails/save', (array) $email );
    if(empty($response)) return FALSE; // Transmission error
    require_once 'response_objects/email.class.php';
    if(!empty( $response->data )) $response->data = new Emfl_Email($response->data);
    return $response;
  }
  
  /**
   * Emails / copy
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
   * populated Emfl_Email object with email details.
   *
   * @param int $email_id The email ID
   * @return false|Emfl_Response
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/emails/copy
   */
  function emails_copy( $email_id ) {
    $response = $this->call('emails/copy', array('emailID' => $email_id));
    if(empty($response)) return FALSE; // Transmission error
    require_once 'response_objects/email.class.php';
    if(!empty( $response->data )) $response->data = new Emfl_Email($response->data);
    return $response;
  }

  /**
   * Emails / delete
   *
   * The return value could be FALSE if a transmission error occurred,
   * like being blocked by the Platform or a network issue.
   *
   * Otherwise even bad API calls will get an Emfl_Response object
   * that corresponds with the Platform's response format. See here:
   * http://apidocs.emailer.emfluence.com/#responses
   *
   * Don't forget to check the 'success' property before assuming that
   * the operation occurred correctly. If an error occurred, the 'errors'
   * property will have details.
   *
   * @param int $email_id
   * @return false|Emfl_Response
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/emails/delete
   */
  function emails_delete( $email_id ) {
    $response = $this->call('emails/delete', array('emailID' => $email_id) );
    if(empty($response)) return FALSE; // Transmission error
    return $response;
  }

  /**
   * Emails / schedule
   *
   * The return value could be FALSE if a transmission error occurred,
   * like being blocked by the Platform or a network issue.
   *
   * Otherwise even bad API calls will get an Emfl_Response object
   * that corresponds with the Platform's response format. See here:
   * http://apidocs.emailer.emfluence.com/#responses
   *
   * Don't forget to check the 'success' property before assuming that
   * the operation occurred correctly. If an error occurred, the 'errors' 
   * property will have details.
   *
   * @param int $email_id The email ID
   * @param string $schedule_send_time Future date when email should be sent, in GMT. Format: Y-m-d H:i:s
   * @return false|Emfl_Response
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/emails/copy
   */
  function emails_schedule( $email_id, $schedule_send_time ) {
    $response = $this->call('emails/schedule', array(
        'emailID' => $email_id, 
        'scheduleSendTime' => $schedule_send_time
      ));
    if(empty($response)) return FALSE; // Transmission error
    return $response;
  }
  
  /**
   * Emails / sendTest
   *
   * The return value could be FALSE if a transmission error occurred,
   * like being blocked by the Platform or a network issue.
   *
   * Otherwise even bad API calls will get an Emfl_Response object
   * that corresponds with the Platform's response format. See here:
   * http://apidocs.emailer.emfluence.com/#responses
   *
   * Don't forget to check the 'success' property before assuming that
   * the operation occurred correctly. If an error occurred, the 'errors' 
   * property will have details.
   *
   * @param int $email_id The email ID
   * @param string $recipient Email address to send the test to.
   * @return false|Emfl_Response
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/emails/sendTest
   */
  function emails_sendTest( $email_id, $recipient ) {
    $response = $this->call('emails/sendTest', array(
        'emailID' => $email_id,
        'recipientEmail' => $recipient
    ));
    if(empty($response)) return FALSE; // Transmission error
    return $response;
  }

  /**
   * Emails / sendTransactional
   *
   * The return value could be FALSE if a transmission error occurred,
   * like being blocked by the Platform or a network issue.
   *
   * Otherwise even bad API calls will get an Emfl_Response object
   * that corresponds with the Platform's response format. See here:
   * http://apidocs.emailer.emfluence.com/#responses
   *
   * Don't forget to check the 'success' property before assuming that
   * the operation occurred correctly. If an error occurred, the 'errors'
   * property will have details.
   *
   * @param Emfl_Email $email The email object
   * @param Emfl_Contact[] $contacts Recipients (will also get saved as contacts)
   * @return false|Emfl_Response
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/emails/sendTransactional
   */
  function emails_sendTransactional( $email, $contacts, $category = NULL, $ignore_suppression = FALSE ) {
    $params = array(
        'email' => $email,
        'contacts' => $contacts
    );
    if(!empty($category)) $params['transactionalCategory'] = $category;
    if($ignore_suppression) $params['ignoreContactSuppression'] = TRUE;
    $response = $this->call('emails/sendTransactional', $params);
    if(empty($response)) return FALSE; // Transmission error
    return $response;
  }


  ///////////////////
  // Email Reports //
  ///////////////////

  /**
   * EmailReports / recipients
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
   * populated Emfl_PagingPlusRecords object with paging details and a records
   * list of Emfl_EmailReport_Recipient recipients.
   *
   * @param array $params Keyed values, per API documentation
   * @return false|Emfl_Response
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/emailReports/recipients
   */
  function emailreports_recipients( $params = array() ) {
    $response = $this->call('emailReports/recipients', $params);
    if(empty($response)) return FALSE; // Transmission error
    if(!empty($response->data)) {
      require_once 'response_objects/wrapper.pagingplusrecords.class.php';
      require_once 'response_objects/emailreports.recipient.class.php';
      $response->data = new Emfl_PagingPlusRecords(
          $response->data->paging,
          $response->data->records,
          'Emfl_EmailReport_Recipient'
        );
    }
    return $response;
  }

  /**
   * EmailReports / views
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
   * populated Emfl_PagingPlusRecords object with paging details and a records
   * list of Emfl_EmailReport_Recipient recipients.
   *
   * @param array $params Keyed values, per API documentation
   * @return false|Emfl_Response
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/emailReports/views
   */
  function emailreports_views( $params = array() ) {
    $response = $this->call('emailReports/views', $params);
    if(empty($response)) return FALSE; // Transmission error
    if(!empty($response->data)) {
      require_once 'response_objects/wrapper.pagingplusrecords.class.php';
      require_once 'response_objects/emailreports.view.class.php';
      $response->data = new Emfl_PagingPlusRecords(
          $response->data->paging,
          $response->data->records,
          'Emfl_EmailReport_View'
        );
    }
    return $response;
  }

  /**
   * EmailReports / clicks
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
   * populated Emfl_PagingPlusRecords object with paging details and a records
   * list of Emfl_EmailReport_Recipient recipients.
   *
   * @param array $params Keyed values, per API documentation
   * @return false|Emfl_Response
   * @see https://apidocs.emailer.emfluence.com/v1/endpoints/emailReports/clicks
   */
  function emailreports_clicks( $params = array() ) {
    $response = $this->call('emailReports/clicks', $params);
    if(empty($response)) return FALSE; // Transmission error
    if(!empty($response->data)) {
      require_once 'response_objects/wrapper.pagingplusrecords.class.php';
      require_once 'response_objects/emailreports.click.class.php';
      $response->data = new Emfl_PagingPlusRecords(
          $response->data->paging,
          $response->data->records,
          'Emfl_EmailReport_Click'
        );
    }
    return $response;
  }
}

/**
 * Class Emfl_Platform_API_Errors
 * Provides basic error storage and retrieval
 */
class Emfl_Platform_API_Errors {
  protected $errors = array();

  /**
   * Adds an error to the list
   * @param $error
   */
  function add($error){
    $this->errors[] = $error;
  }

  /**
   * Retrieves all errors
   * @param boolean $clear = FALSE
   * @return array
   */
  function get($clear = FALSE){
    $errors = $this->errors;
    if( $clear ){
      $this->clear();
    }
    return $errors;
  }

  /**
   * Retrieves the last error
   * @param boolean $clear = FALSE
   * @return string
   */
  function get_last($clear = FALSE){
    $errors = $this->errors;
    if( $clear ){
      $this->clear();
    }
    return array_pop($errors);
  }

  /**
   * Empties the error list
   */
  function clear(){
    $this->errors = array();
  }
}
