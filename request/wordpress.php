<?php

/**
 * Wordpress request handler
 * @param string $url
 * @param array $params
 * @param string $endpoint
 * @param int $timeout in seconds
 * @return object|string
 */
function emfl_platform_api_wordpress_request($url, $params, $endpoint, $timeout) {
  if(!function_exists('wp_remote_post')) return FALSE;
  
  $response = wp_remote_post(
      $url,
      array(
          'timeout' => $timeout,
          'headers' => array( 'Content-Type' => 'application/json' ),
          'body' => json_encode( (object) $params )
      )
  );

  if( !is_wp_error($response) ) {
    return (object) array(
        'data' => $response['body'],
        'code' => $response['response']['code']
    );
  } else {
    return 'wp_remote_post returned an error for ' . $endpoint . ': ' . var_export($response, TRUE);
  }
}
