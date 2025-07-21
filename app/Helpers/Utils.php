<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class Utils {
  public static function getDomainFromUrl($string, $hostNameOnly = false){
    $hostUrl = strtolower( string: parse_url( $string , PHP_URL_HOST ) );
    if ($hostNameOnly && preg_match('/(?:www\.)?([a-z0-9-]+)\.com/i', $hostUrl, $matches)) {
      return $matches[1];
    }
    return $hostUrl;
  }

  public static function slug($model, $name, $exceptId = null) {
    $slug = Str::slug($name);
    $model = $model::whereSlug($slug);
    if ($exceptId) {
      $model = $model->where('id', '<>', $exceptId);
    }

    $slugCount = $model->count();
    if ($slugCount) {
      $slug .= '-' . $slugCount + 1;
    }

    return $slug;
  }
}