<?php

// A collection of the routines I used from www.tsugi.org

    function getCurl($url, $header=false) {
      if ( ! function_exists('curl_init') ) return false;
      global $last_http_response;
      global $LastHeadersSent;
      global $LastHeadersReceived;

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);

      // Make sure that the header is an array and pitch white space
      $LastHeadersSent = trim($header);
      $header = explode("\n", trim($header));
      $htrim = Array();
      foreach ( $header as $h ) {
        $htrim[] = trim($h);
      }
      curl_setopt ($ch, CURLOPT_HTTPHEADER, $htrim);

      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // ask for results to be returned
      curl_setopt($ch, CURLOPT_HEADER, 1);

      // Thanks to more and more PHP's not shipping with CA's installed
      // This becomes necessary
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

      // Send to remote and return data to caller.
      $result = curl_exec($ch);
      $info = curl_getinfo($ch);
      $last_http_response = $info['http_code'];
      $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
      $LastHeadersReceived = substr($result, 0, $header_size);
      $body = substr($result, $header_size);
      if ( $body === false ) $body = "";
      curl_close($ch);
      return $body;
    }

// Convienence method to get the local path if we are doing
function route_get_local_path($dir) {
    $uri = $_SERVER['REQUEST_URI'];     // /tsugi/lti/some/cool/stuff
    $root = $_SERVER['DOCUMENT_ROOT'];  // /Applications/MAMP/htdocs
    $cwd = $dir;                        // /Applications/MAMP/htdocs/tsugi/lti
    if ( strlen($cwd) < strlen($root) + 1 ) return $uri;
    $lwd = substr($cwd,strlen($root));  // /tsugi/lti
    if ( strlen($uri) < strlen($lwd) + 2 ) return $uri;
    $local = substr($uri,strlen($lwd)+1); // some/cool/stuff
    return $local;
}

