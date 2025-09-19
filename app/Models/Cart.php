<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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
    static::addGlobalScope('user_id', function (Builder $builder) {
      if ($user = Auth::user()) {
        $builder->where('user_id', $user->id);
      }
    });
  }

  public function items() {
    return $this->hasMany(CartItem::class);
  }

}
