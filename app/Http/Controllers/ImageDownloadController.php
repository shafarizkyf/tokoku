<?php

namespace App\Http\Controllers;

use App\Helpers\Image;
use App\Models\ProductImage;

class ImageDownloadController extends Controller {

  public function download() {
    $images = ProductImage::where('path', 'like', "http%")->take(20)->get();

    $failedUrls = [];
    foreach($images as $image) {
      $savePath = "products/{$image->product_id}";
      $path = Image::saveImageFromUrl($image->path, $savePath);

      if ($path) {
        $image->path = $path;
        $image->save();
      } else {
        $failedUrls[] = $image->url;
      }
    }

    if ($count = count($failedUrls)) {
      return response([
        'message' => "{$count} image(s) failed to download"
      ]);
    }

    return response([
      'message' => "All image downloaded"
    ]);
  }

}
