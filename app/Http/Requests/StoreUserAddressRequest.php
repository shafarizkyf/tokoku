<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreUserAddressRequest extends FormRequest
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
            'name' => 'required|max:50',
            'phone_number' => 'required|max:20',
            'address_detail' => 'required',
            'province_id' => 'required',
            'regency_id' => 'required',
            'district_id' => 'required',
            'village_id' => 'required',
            'postal_code' => 'required|digits:5',
            'note' => 'nullable|max:150'
        ];
    }
}
