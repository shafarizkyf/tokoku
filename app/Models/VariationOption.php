<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariationOption extends Model {

  protected $fillable = [
    'variation_attribute_id',
    'value'
  ];

  public $timestamps = false;

}
