<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageDownloadQueue extends Model {

  protected $fillable = [
    'url',
    'options',
    'save_path'
  ];

  public function getOptionsAttribute($value) {
    return $value ? json_decode($value) : null;
  }

}
