<?php

namespace App\Models;

use App\Models\Scopes\OrderByName;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([OrderByName::class])]
class Village extends Model {

  protected $table = 'reg_villages';

  protected $fillable = [
    'id',
    'district_id',
    'name',
  ];

  public $timestamps = false;

  public function district() {
    return $this->belongsTo(District::class);
  }


}
