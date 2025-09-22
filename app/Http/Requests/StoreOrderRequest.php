<?php

namespace App\Http\Requests;

use App\Rules\EnsureStockExist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.product_variation_id' => 'exists:product_variations,id',
            'items.*.quantity' => [
                'required',
                'numeric',
                function($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $productVariationId = request()->input("items.$index.product_variation_id");
                    (new EnsureStockExist($productVariationId))
                        ->validate($attribute, $value, $fail);
                }
            ],
            'payment_method' => 'required',
            'shipping' => 'required',
            'shipping.receiver_name' => 'required|max:50',
            'shipping.address' => 'required',
            'shipping.province_id' => 'required',
            'shipping.regency_id' => 'required',
            'shipping.district_id' => 'required',
            'shipping.village_id' => 'required',
            'shipping.postal_code' => 'required|digits:5',
            'delivery' => 'required',
            'delivery.shipping_name' => 'required',
            'delivery.service_name' => 'required',
        ];
    }
}
