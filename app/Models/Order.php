<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model {

  public $appends = [
    'order_status'
  ];

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

  public function getPaymentResponseAttribute($value){
    return $value ? json_decode($value) : null;
  }

  public function getPaymentFeeAttribute() {
    return $this->payment_response && isset($this->payment_response->data) ? $this->payment_response->data->total_fee : 0;
  }

  public function getGrandTotalAttribute($value) {
    return $value + $this->payment_fee;
  }

  public function getOrderStatusAttribute() {
    switch ($this->status) {
      case 'pending':
        return 'Menunggu Pembayaran';
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

  public function village() {
    return $this->belongsTo(Village::class);
  }

  public function scopeSearch($query, $value) {
    return $query->where('code', $value)
      ->orWhere('recipient_name', 'like', "{$value}%");
  }

}
