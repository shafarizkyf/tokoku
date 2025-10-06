<?php

namespace App\Models;

use App\Models\Scopes\UserOwnedScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([UserOwnedScope::class])]
class Order extends Model {

  public $appends = [
    'order_status',
    'is_cancelable',
  ];

  protected function casts() {
    return [
      'created_at' => 'datetime'
    ];
  }

  public function orderDetails() {
    return $this->hasMany(OrderDetail::class);
  }

  public function getFullAddressAttribute() {
    return $this->address_detail . ', ' .
      $this->village->name . ', ' .
      $this->district->name . ', ' .
      $this->regency->name . ', ' .
      $this->province->name . ' ' .
      "({$this->postal_code})";
  }

  public function getIsCancelableAttribute() {
    return $this->status == 'pending';
  }

  public function getPaymentResponseAttribute($value){
    return $value ? json_decode($value) : null;
  }

  public function getPaymentFeeAttribute() {
    return $this->payment_response && isset($this->payment_response->data) ? $this->payment_response->data->total_fee : 0;
  }

  public function getResiTrackAttribute() {
    return $this->resi_track_response ? json_decode($this->resi_track_response)->data : null;
  }

  public function getGrandTotalAttribute($value) {
    return $value + $this->payment_fee;
  }

  public function getOrderStatusAttribute() {
    switch ($this->status) {
      case 'pending':
        $message = 'Unknown';
        switch($this->payment_status) {
          case 'unpaid':
            $message = 'Menunggu Pembayaran';
            break;
          case 'expired':
            $message = 'Pembayaran Kadaluarsa';
            break;
          case 'failed':
            $message = 'Pembayaran Gagal';
            break;
        }
        return $message;
      case 'paid':
        return 'Dalam Proses';
      case 'shipped':
        return 'Dalam Pengiriman';
      case 'completed':
        return 'Selesai';
      case 'cancelled':
        return 'Dibatalkan';
    }
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

  public function scopeSearch($query, $value) {
    return $query->where('code', $value)
      ->orWhere('recipient_name', 'like', "{$value}%");
  }

}
