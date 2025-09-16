<?php

namespace App\Models;

use App\Models\Scopes\OrderByName;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([OrderByName::class])]
class Province extends Model {

  protected $table = 'reg_provinces';

  public function regencies() {
    return $this->hasMany(Regency::class);
  }

}
