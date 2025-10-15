<?php

namespace App\Rules;

use App\Models\ProductVariation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EnsureStockExist implements ValidationRule
{

    protected $productVariationId;
    protected $productName;

    public function __construct($productVariationId)
    {
        $this->productVariationId = $productVariationId;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void  {
        $variation = ProductVariation::find($this->productVariationId);

        if (!$variation) {
            $variationRef = ProductVariation::withTrashed()->find($this->productVariationId);
            $fail("Produk '{$variationRef->product->name}' sudah tidak tersedia");
        } else if ($variation->stock < $value) {
            $fail($variation->product->name . ': Stok tidak mencukupi.');
        }
    }
}
