<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProductUpdateRequest extends FormRequest
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
            'name' => 'required',
            'description' => 'required',
            'price' => $this->variations ? 'nullable' : 'required',
            'condition' => 'required',
            'variations' => 'array',
            'variations.*.price' => 'required',
            'variations.*.stock' => 'required',
            'variations.*.weight' => 'required',
            'variations.*.attributes' => 'array',
            'image_urls' => 'nullable|array',
        ];
    }
}
