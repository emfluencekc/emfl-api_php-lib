<?php

/**
 * The fallback method: cURL.
 * @param string $url
 * @param array $params
 * @param int $timeout
 * @return object
 */
function emfl_platform_api_generic_request($url, $params, $timeout) {
  if(!function_exists('curl_init')) return FALSE;
  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
  curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
  curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode( (object) $params ) );
  $json_response = curl_exec($curl);
  $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  curl_close($curl);
  return (object) array(
      'data' => $json_response,
      'code' => $status
  );
}
