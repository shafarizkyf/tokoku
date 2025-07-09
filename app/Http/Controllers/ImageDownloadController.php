<?php

namespace App\Http\Controllers;

use App\Helpers\Image;
use App\Models\ImageDownloadQueue;
use App\Models\ProductImage;

class ImageDownloadController extends Controller {

  public function download() {
    $images = ImageDownloadQueue::take(20)->get();

    $failedUrls = [];
    foreach($images as $image) {
      $path = Image::saveImageFromUrl($image->url, $image->save_path);

      if ($path) {
        ProductImage::create([
          'product_id' => $image->options->product_id,
          'path' => $path,
        ]);
      } else {
        $failedUrls[] = $image->url;
      }

      $image->delete();
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
