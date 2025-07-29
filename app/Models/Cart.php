<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
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

  protected static function booted(): void {
    static::addGlobalScope('session_id', function (Builder $builder) {
      $builder->where('session_id', request()->cookie('session_id'));
    });
  }

  public function items() {
    return $this->hasMany(CartItem::class);
  }

}
