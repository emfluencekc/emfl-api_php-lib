<?php

/**
 * Drupal request handler
 * @param string $url
 * @param array $params
 * @param int $timeout
 * @return mixed
 */
function emfl_platform_api_drupal_request($url, $params, $timeout) {
  if(!(function_exists('drupal_http_request') && defined('VERSION'))) return FALSE;
  if( VERSION > 7 ) {
    return drupal_http_request(
        $url,
        array(
            'headers' => array( 'Content-Type' => 'application/json' ),
            'method' => 'POST',
            'data' => json_encode( (object) $params),
            'timeout' => $timeout
        )
    );
  } else {
    return drupal_http_request(
        $url,
        array( 'Content-Type' => 'application/json' ),
        'POST',
        json_encode( (object) $params),
        1,
        $timeout
    );
  }
}
