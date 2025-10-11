<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBannerRequest;
use App\Models\Banner;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller {

  public function index() {
    return Banner::latest()->take(10)->get();
  }

  public function store(StoreBannerRequest $request) {
    $data = $request->validated();
    $data['path'] = $request->file('image')->storePublicly('banners', 'public');

    Banner::create($data);

    return response([
        'success' => true,
        'message' => 'Tersimpan'
    ]);
  }

  public function update(Banner $banner, StoreBannerRequest $request) {
    $data = $request->validated();

    if ($request->file('image')) {
        $data['path'] = $request->file('image')->storePublicly('banners', 'public');
        Storage::disk('public')->delete($banner->path);
    }

    $banner->update($data);

    return response([
        'success' => true,
        'message' => 'Diperbarui'
    ]);
  }

  public function destroy(Banner $banner) {
    Storage::disk('public')->delete($banner->path);
    $banner->delete();

    return response([
        'success' => true,
        'message' => 'Dihapus'
    ]);
  }

}
