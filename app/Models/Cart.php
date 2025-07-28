<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model {

  protected $fillable = [
    'user_id',
    'session_id'
  ];

  protected $hidden = [
    'id',
    'created_at',
    'updated_at',
  ];

  public function items() {
    return $this->hasMany(CartItems::class);
  }

}
