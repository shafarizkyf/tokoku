<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkStockRequest extends FormRequest {

  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array {
    return [
      'product_ids' => 'required|array|min:1',
      'product_ids.*' => 'required|exists:products,id',
      'stock_action' => 'required|in:set,add,subtract',
      'stock_value' => 'required|integer|min:0',
    ];
  }

  /**
   * Get custom messages for validator errors.
   *
   * @return array
   */
  public function messages(): array {
    return [
      'product_ids.required' => 'Pilih minimal 1 produk',
      'product_ids.array' => 'Format produk tidak valid',
      'product_ids.min' => 'Pilih minimal 1 produk',
      'product_ids.*.exists' => 'Produk tidak ditemukan',
      'stock_action.required' => 'Aksi stok harus dipilih',
      'stock_action.in' => 'Aksi stok tidak valid',
      'stock_value.required' => 'Nilai stok harus diisi',
      'stock_value.integer' => 'Nilai stok harus berupa angka',
      'stock_value.min' => 'Nilai stok tidak boleh negatif',
    ];
  }

}
