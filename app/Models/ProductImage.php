<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model {
  protected $fillable = [
    'product_id',
    'path'
  ];

  protected $hidden = [
    'id',
    'product_id',
    'path',
    'created_at',
    'updated_at'
  ];

  public $appends = [
    'url'
  ];

  public function getUrlAttribute() {
    return str_contains($this->path, 'http') // path may still refer to origin host
      ? $this->path
      : url(Storage::url($this->path)); // already downloaded to local
  }

}
