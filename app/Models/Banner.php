<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model {

  protected $fillable = [
    'path',
    'link',
    'description',
  ];

  protected $hidden = [
    'path'
  ];

  public $appends = [
    'url'
  ];

  public function getUrlAttribute() {
    return url(Storage::url($this->path));
  }

}
