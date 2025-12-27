<?php

namespace App\Models;

use App\Models\Scopes\UserOwnedScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

#[ScopedBy([UserOwnedScope::class])]
class UserAddress extends Model {

  protected $fillable = [
    'user_id',
    'name',
    'phone_number',
    'address_detail',
    'province_id',
    'regency_id',
    'district_id',
    'village_id',
    'postal_code',
    'note',
  ];

  public $appends = [
    'full_address'
  ];

  protected static function booted() {
    self::created(function(){
      Cache::forget('addresses.' . Auth::id());
    });

    self::updated(function(){
      Cache::forget('addresses.' . Auth::id());
    });

    self::deleted(function(){
      Cache::forget('addresses.' . Auth::id());
    });
  }

  public function getFullAddressAttribute() {
    return $this->address_detail . ', ' .
      $this->village->name . ', ' .
      $this->district->name . ', ' .
      $this->regency->name . ', ' .
      $this->province->name . ' ' .
      "({$this->postal_code})";
  }

  public function district() {
    return $this->belongsTo(District::class);
  }

  public function province() {
    return $this->belongsTo(Province::class);
  }

  public function regency() {
    return $this->belongsTo(Regency::class);
  }

  public function user() {
    return $this->belongsTo(User::class);
  }

  public function village() {
    return $this->belongsTo(Village::class);
  }


}
