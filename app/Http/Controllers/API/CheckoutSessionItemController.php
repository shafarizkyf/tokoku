<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CheckoutSession;
use App\Models\CheckoutSessionItem;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckoutSessionItemController extends Controller
{
    protected function getOrCreateSession(Request $request): CheckoutSession
    {
        $sessionId = $request->header('X-Checkout-Session') ?: $request->session_id;

        if ($sessionId) {
            $session = CheckoutSession::findBySessionId($sessionId);
            if ($session) {
                return $session;
            }
        }

        return CheckoutSession::create([
            'session_id' => (string) Str::uuid(),
            'expires_at' => now()->addHours(24),
        ]);
    }

    protected function recalculateSessionTotals(CheckoutSession $session): void
    {
        $subtotal = 0;
        $totalWeight = 0;

        foreach ($session->items as $item) {
            $price = $item->price_discount_at_time ? $item->price_discount_at_time : $item->price_at_time;
            $subtotal += $price * $item->quantity;
            $totalWeight += ($item->productVariation?->weight ?? 500) * $item->quantity;
        }

        $session->subtotal = $subtotal;
        $session->total_weight = $totalWeight;
        $session->save();
    }

    public function init() {
        $session = CheckoutSession::create([
            'session_id' => (string) Str::uuid(),
            'expires_at' => now()->addHours(value: 24),
        ]);

        return [
            'session_id' => $session->session_id,
            'expires_at' => $session->expires_at,
        ];
    }

    public function index(Request $request)
    {
        $session = $this->getOrCreateSession($request);

        $items = $session->items()
            ->with(['product:id,name,slug', 'productVariation'])
            ->get()
            ->map(function ($item) {
                $options = [];
                if ($item->productVariation) {
                    foreach ($item->productVariation->variationOptions as $vo) {
                        $options[] = $vo->variationOption->value;
                    }
                }

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? null,
                    'product_image' => $item->product->images->first() ?? null,
                    'product_variation_id' => $item->product_variation_id,
                    'product_stock' => $item->productVariation->stock ?? 0,
                    'quantity' => $item->quantity,
                    'price' => $item->price_at_time,
                    'price_discount' => $item->price_discount_at_time,
                    'subtotal' => $item->subtotal,
                    'options' => $options,
                ];
            });

        return response([
            'success' => true,
            'session_id' => $session->session_id,
            'items' => $items,
            'subtotal' => $session->subtotal,
            'total_weight' => $session->total_weight,
            'item_count' => $items->sum('quantity'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variation_id' => 'required|exists:product_variations,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $session = $this->getOrCreateSession($request);

        if (!app()->environment('production')) {
            Log::channel('cart')->info('checkout_session_add', array_merge($request->all(), [
                'session_id' => $session->session_id,
            ]));
        }

        $productVariation = ProductVariation::with('product')->find($request->product_variation_id);

        if (!$productVariation || !$productVariation->product) {
            return response([
                'success' => false,
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        if ($productVariation->stock < $request->quantity) {
            return response([
                'success' => false,
                'message' => 'Stok tidak mencukupi',
            ], 400);
        }

        $response = response([
            'success' => false,
            'message' => 'Unexpected Error',
        ], 500);

        DB::transaction(function () use ($request, $session, $productVariation, &$response) {
            $existingItem = $session->items()->where([
                'product_id' => $request->product_id,
                'product_variation_id' => $request->product_variation_id,
            ])->first();

            if ($existingItem) {
                $newQuantity = $existingItem->quantity + $request->quantity;
                if ($newQuantity > $productVariation->stock) {
                    $response = response([
                        'success' => false,
                        'message' => 'Stok tidak mencukupi',
                    ], 400);
                    return;
                }

                $existingItem->quantity = $newQuantity;
                $existingItem->save();

                $id = $existingItem->id;
            } else {
                $item = CheckoutSessionItem::create([
                    'checkout_session_id' => $session->id,
                    'product_id' => $request->product_id,
                    'product_variation_id' => $request->product_variation_id,
                    'quantity' => $request->quantity,
                    'price_at_time' => $productVariation->price,
                    'price_discount_at_time' => $productVariation->discount_price,
                ]);
                $id = $item->id;
            }

            $this->recalculateSessionTotals($session);

            $response = response([
                'success' => true,
                'message' => 'Barang telah ditambahkan ke checkout',
                'data' => [
                    'session_id' => $session->session_id,
                    'checkout_session_item_id' => $id,
                ]
            ]);
        });

        return $response;
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $session = $this->getOrCreateSession($request);

        $item = $session->items()->find($id);

        if (!$item) {
            return response([
                'success' => false,
                'message' => 'Item tidak ditemukan',
            ], 404);
        }

        $productVariation = $item->productVariation;

        if (!$productVariation || $productVariation->stock < $request->quantity) {
            return response([
                'success' => false,
                'message' => 'Stok tidak mencukupi',
            ], 400);
        }

        $item->quantity = $request->quantity;
        $item->save();

        $this->recalculateSessionTotals($session);

        return response([
            'success' => true,
            'message' => 'Jumlah barang telah diperbarui',
            'data' => [
                'session_id' => $session->session_id,
            ]
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $session = $this->getOrCreateSession($request);

        $item = $session->items()->find($id);

        if (!$item) {
            return response([
                'success' => false,
                'message' => 'Item tidak ditemukan',
            ], 404);
        }

        $item->delete();

        $this->recalculateSessionTotals($session);

        return response([
            'success' => true,
            'message' => 'Barang telah dihapus dari checkout',
            'data' => [
                'session_id' => $session->session_id,
            ]
        ]);
    }

    public function clear(Request $request)
    {
        $session = $this->getOrCreateSession($request);

        $session->items()->delete();

        $this->recalculateSessionTotals($session);

        return response([
            'success' => true,
            'message' => 'Checkout telah dikosongkan',
        ]);
    }

    public function count(Request $request)
    {
        $session = $this->getOrCreateSession($request);

        $count = $session->items()->sum('quantity');

        return response([
            'items_count' => $count,
            'session_id' => $session->session_id,
        ]);
    }
}
