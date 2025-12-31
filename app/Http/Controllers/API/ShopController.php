<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller {

  /**
   * Get shop settings
   */
  public function show() {
    $shop = Shop::first();
    
    if (!$shop) {
      return response()->json([
        'message' => 'Shop settings not found'
      ], 404);
    }

    return response()->json([
      'data' => $shop
    ]);
  }

  /**
   * Update shop settings
   */
  public function update(Request $request) {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
      'description' => 'required|string',
      'image' => 'nullable|image|mimes:jpeg,jpg,png|max:2048'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $shop = Shop::first();
    
    if (!$shop) {
      $shop = new Shop();
    }

    $shop->name = $request->name;
    $shop->description = $request->description;

    // Handle image upload
    if ($request->hasFile('image')) {
      // Delete old image if exists
      if ($shop->image_path && Storage::disk('public')->exists($shop->image_path)) {
        Storage::disk('public')->delete($shop->image_path);
      }

      $image = $request->file('image');
      $imagePath = $image->store('shops', 'public');
      $shop->image_path = $imagePath;
    }

    $shop->save();

    return response()->json([
      'message' => 'Shop settings updated successfully',
      'data' => $shop
    ]);
  }

}
