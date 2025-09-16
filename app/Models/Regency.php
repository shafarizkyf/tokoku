<?php

namespace App\Models;

use App\Models\Scopes\OrderByName;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([OrderByName::class])]
class Regency extends Model {

  protected $table = 'reg_regencies';

  public function districts() {
    return $this->hasMany(District::class);
  }

  public function province() {
    return $this->belongsTo(Province::class);
  }

}
