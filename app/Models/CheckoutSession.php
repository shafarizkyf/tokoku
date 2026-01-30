<?php

namespace App\Models;

use App\Helpers\Utils;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CheckoutSession extends Model
{
    protected $fillable = [
        'session_id',
        'public_token',
        'user_id',
        'cart_id',
        'order_id',
        'recipient_name',
        'recipient_email',
        'recipient_phone',
        'address_detail',
        'province_id',
        'regency_id',
        'district_id',
        'village_id',
        'postal_code',
        'note',
        'subtotal',
        'total_weight',
        'shipping_price',
        'discount',
        'courier',
        'service_type',
        'payment_method',
        'payment_status',
        'payment_reference',
        'payment_response',
        'payment_url',
        'payment_expired_at',
        'paid_at',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'total_weight' => 'integer',
        'shipping_price' => 'integer',
        'discount' => 'integer',
        'payment_response' => 'array',
        'payment_expired_at' => 'datetime',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->session_id)) {
                $model->session_id = (string) Str::uuid();
            }
            if (empty($model->public_token)) {
                $model->public_token = hash('sha256', $model->session_id.now()->timestamp);
            }
            if (empty($model->expires_at)) {
                $model->expires_at = now()->addHours(24);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(CheckoutSessionItem::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function regency()
    {
        return $this->belongsTo(Regency::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function village()
    {
        return $this->belongsTo(Village::class);
    }

    public function getGrandTotalAttribute(): int
    {
        return $this->subtotal + $this->shipping_price - $this->discount;
    }

    public function getFullAddressAttribute(): ?string
    {
        if (! $this->address_detail) {
            return null;
        }

        $parts = [$this->address_detail];
        $parts[] = $this->village?->name ?? '';
        $parts[] = $this->district?->name ?? '';
        $parts[] = $this->regency?->name ?? '';
        $parts[] = ($this->province?->name ?? '').' '.($this->postal_code ?? '');

        return implode(', ', array_filter($parts));
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function markAsPaid(string $reference, array $response = []): bool
    {
        $this->payment_status = 'paid';
        $this->payment_reference = $reference;
        $this->payment_response = $response;
        $this->paid_at = now();
        $this->status = 'completed';

        return $this->save();
    }

    public function markAsFailed(?string $reference = null, array $response = []): bool
    {
        $this->payment_status = 'failed';
        if ($reference) {
            $this->payment_reference = $reference;
        }
        if (! empty($response)) {
            $this->payment_response = $response;
        }

        return $this->save();
    }

    public function convertToOrder(): ?Order
    {
        if ($this->payment_status !== 'paid') {
            return null;
        }

        do {
            $code = 'INV'.now()->format('Ymd').Utils::generateRandomCode(6);
        } while (Order::where('code', $code)->exists());

        $subtotal = 0;
        $totalWeight = 0;
        foreach ($this->items as $item) {
            $price = $item->price_discount_at_time ?? $item->price_at_time;
            $subtotal += $price * $item->quantity;
            $totalWeight += $item->productVariation?->weight ?? 500;
        }

        $order = Order::create([
            'user_id' => $this->user_id,
            'code' => $code,
            'status' => 'paid',
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'payment_status' => 'paid',
            'payment_response' => json_encode($this->payment_response),
            'payment_expired_at' => $this->payment_expired_at,
            'paid_at' => $this->paid_at,
            'total_price' => $subtotal,
            'total_weight' => $totalWeight,
            'shipping_price' => $this->shipping_price,
            'total_discount' => $this->discount,
            'grand_total' => $this->grand_total,
            'courier' => $this->courier,
            'recipient_name' => $this->recipient_name,
            'recipient_email' => $this->recipient_email,
            'recipient_phone' => $this->recipient_phone,
            'address_detail' => $this->address_detail,
            'province_id' => $this->province_id,
            'regency_id' => $this->regency_id,
            'district_id' => $this->district_id,
            'village_id' => $this->village_id,
            'postal_code' => $this->postal_code,
            'note' => $this->note,
        ]);

        if ($order && $this->items->isNotEmpty()) {
            foreach ($this->items as $item) {
                $price = $item->price_discount_at_time ?? $item->price_at_time;
                $subtotal = $price * $item->quantity;
                $weight = $item->productVariation?->weight ?? 500;

                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_variation_id' => $item->product_variation_id,
                    'name_snapshot' => $item->product->name ?? 'Product',
                    'variation_snapshot' => null,
                    'price' => $item->price_at_time,
                    'quantity' => $item->quantity,
                    'discount' => $item->price_discount_at_time ? ($item->price_at_time - $item->price_discount_at_time) * $item->quantity : 0,
                    'subtotal' => $subtotal,
                    'weight' => $weight * $item->quantity,
                ]);
            }
        }

        $this->order_id = $order->id;
        $this->status = 'completed';
        $this->save();

        return $order;
    }

    public function linkToUser(int $userId): bool
    {
        $this->user_id = $userId;

        return $this->save();
    }

    public function snapshotCartItems(Cart $cart): self
    {
        foreach ($cart->items as $item) {
            CheckoutSessionItem::create([
                'checkout_session_id' => $this->id,
                'product_id' => $item->product_id,
                'product_variation_id' => $item->product_variation_id,
                'quantity' => $item->quantity,
                'price_at_time' => $item->price_at_time,
                'price_discount_at_time' => $item->price_discount_at_time,
            ]);
        }

        return $this;
    }

    public static function findBySessionId(string $sessionId): ?self
    {
        return static::where('session_id', $sessionId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();
    }

    public static function findByPublicToken(string $publicToken): ?self
    {
        return static::where('public_token', $publicToken)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();
    }

    public static function validatePublicToken(string $publicToken): ?self
    {
        return static::findByPublicToken($publicToken);
    }

    public static function calculateWeightAndValue(?string $sessionId = null): array
    {
        if (! $sessionId) {
            return [
                'weight_in_kg' => 0,
                'package_value' => 0,
            ];
        }

        $session = static::findBySessionId($sessionId);

        if (! $session) {
            return [
                'weight_in_kg' => 0,
                'package_value' => 0,
            ];
        }

        $totalWeightInGrams = 0;
        $totalItemValue = 0;

        $items = $session->items()
            ->with('productVariation')
            ->get();

        foreach ($items as $item) {
            $variation = $item->productVariation;
            if ($variation) {
                $weight = $variation->weight;
                $price = $variation->discount_price ? $variation->discount_price : $variation->price;
            } else {
                $weight = 500;
                $price = $item->price_discount_at_time ? $item->price_discount_at_time : $item->price_at_time;
            }

            $totalWeightInGrams += $weight * $item->quantity;
            $totalItemValue += $price * $item->quantity;
        }

        return [
            'weight_in_kg' => $totalWeightInGrams / 1000,
            'package_value' => $totalItemValue,
        ];
    }

    public static function findActiveByUser(int $userId): ?self
    {
        return static::where('user_id', $userId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
