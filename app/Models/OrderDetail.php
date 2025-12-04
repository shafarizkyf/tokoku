<?php

namespace App\Models;

use App\Models\Scopes\ProductActive;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model {

  public function product() {
    return $this->belongsTo(Product::class)
      ->withoutGlobalScope(ProductActive::class);
  }

  public function productVariation() {
    return $this->belongsTo(ProductVariation::class);
  }

}
