<?php

namespace App\Helpers;

class Utils {
  public static function getDomainFromUrl($string, $hostNameOnly = false){
    $hostUrl = strtolower( string: parse_url( $string , PHP_URL_HOST ) );
    if ($hostNameOnly && preg_match('/(?:www\.)?([a-z0-9-]+)\.com/i', $hostUrl, $matches)) {
      return $matches[1];
    }
    return $hostUrl;
  }
}