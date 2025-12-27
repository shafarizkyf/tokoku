<?php

namespace App\Models;

use App\Models\Scopes\OrderByName;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([OrderByName::class])]
class District extends Model {

  protected $table = 'reg_districts';

  protected $fillable = [
    'id',
    'regency_id',
    'name',
  ];

  public $timestamps = false;

  public function regency() {
    return $this->belongsTo(Regency::class);
  }

  public function villages() {
    return $this->hasMany(Village::class);
  }

}
