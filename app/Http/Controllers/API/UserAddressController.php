<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserAddressRequest;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UserAddressController extends Controller {

  public function index() {
    return Cache::remember('addresses.' . Auth::id(), now()->add('day', 7), function(){
      return UserAddress::all();
    });
  }

  public function store(StoreUserAddressRequest $request) {
    $data = $request->validated();
    $data['user_id'] = Auth::id();
    $address = UserAddress::create($data);

    return response([
      'success' => true,
      'message' => 'Alamat tersimpan'
    ]);
  }

  public function update(UserAddress $address, StoreUserAddressRequest $request) {
    $data = $request->validated();
    $address->update($data);

    return response([
      'success' => true,
      'message' => 'Alamat diperbarui'
    ]);
  }

}
