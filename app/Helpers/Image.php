<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Image {

  public static function getExtensionFromUrl($url) {
    // Get the path part of the URL
    $path = parse_url($url, PHP_URL_PATH);
    // Get the extension (after last dot)
    return pathinfo($path, PATHINFO_EXTENSION);
  }

  public static function saveImageFromUrl($url, $savePath){
    // Get the image data
    $imageData = file_get_contents($url);

    // Check if the image was fetched successfully
    if ($imageData === false) {
      Log::error('Image::saveImageFromUrl', [
        'url' => $url,
        'message' => 'Could not download the image'
      ]);

      return null;
    }

    // Save the image data to the desired path
    $filename = Str::uuid();
    $ext = self::getExtensionFromUrl($url);

    if (strlen($ext) > 3 || !$ext) {
      $ext = "png";
    }

    $path = "{$savePath}/{$filename}.{$ext}";
    $saved = Storage::disk('public')->put($path, $imageData);

    if ($saved) {
      return $path;
    } else {
      Log::error('download image to path', compact('url', 'path', 'ext', 'saved'));
    }

    return null;
  }
}