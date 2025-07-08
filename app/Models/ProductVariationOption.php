<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariationOption extends Model {

  protected $fillable = [
    'product_variation_id',
    'variation_option_id'
  ];

  public $timestamps = false;

}
