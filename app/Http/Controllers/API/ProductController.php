<?php

namespace App\Http\Controllers\API;

use App\Helpers\DataTable;
use App\Helpers\Image;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyProductRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Requests\BulkDiscountRequest;
use App\Http\Requests\BulkStockRequest;

use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Models\ProductVariationOption;
use App\Models\Scopes\ProductActive;
use App\Models\Shop;
use App\Models\VariationAttribute;
use App\Models\VariationOption;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller {

  public function index() {
    if (request('view') == 'datatable') {
      $products = Product::with(['image', 'cheapestVariation'])
        ->withoutGlobalScope(ProductActive::class)
        ->latest();

      return DataTable::ajaxTable($products);
    }

    return Cache::tags(['products'])->remember('products.page.' . request('page', 1), now()->addSeconds(30), function () {
      return Product::with(['image', 'cheapestVariation'])
        ->latest()
        ->paginate(30);
    });
  }

  public function search() {
    return Product::search(request('keyword'))->take(10)->get();
  }

  public function show(Product $product) {
    $product = $product->load('variations', 'images');
    $product['variation_options'] = ProductVariation::options()->where('product_id', $product->id)->get();

    return $product;
  }

  public function store(ProductUpdateRequest $productUpdateRequest) {
    Log::channel('product')->info('store', $productUpdateRequest->toArray());

    $response = response([
      'success' => false,
      'message' => 'Unexpected Error'
    ], 500);

    DB::transaction(function() use ($productUpdateRequest, &$response) {
      $hasVariations = isset($productUpdateRequest->variations) && count($productUpdateRequest->variations);

      $product = new Product;
      $product->store_id = 1;
      $product->name = $productUpdateRequest->name;
      $product->slug = Utils::slug(Product::class, $productUpdateRequest->name);
      $product->description = $productUpdateRequest->description;
      $product->condition = $productUpdateRequest->condition;
      $product->created_by = Auth::id();
      $product->save();

      if (!$hasVariations) {
        ProductVariation::create( [
          'sku' => $product->slug,
          'product_id' => $product->id,
          'price' => $productUpdateRequest->price,
          'stock' => $productUpdateRequest->stock,
          'weight' => $productUpdateRequest->weight,
        ]);
      } else {
        $this->saveProductVariations($product, $productUpdateRequest);
      }

      $response = response([
        'success' => true,
        'message' => 'Tersimpan',
        'data' => [
          'product_id' => $product->id,
        ],
      ]);
    });

    return $response;
  }

  public function update(Product $product, ProductUpdateRequest $productUpdateRequest) {
    Log::channel('product')->info('update', $productUpdateRequest->toArray());

    $response = response([
      'success' => false,
      'message' => 'Unexpected Error'
    ], 500);

    DB::transaction(function() use ($product, $productUpdateRequest, &$response) {
      $prevSlug = $product->slug;
      $hasVariations = isset($productUpdateRequest->variations) && count($productUpdateRequest->variations);

      $product->name = $productUpdateRequest->name;
      $product->slug = Utils::slug(Product::class, $productUpdateRequest->name, $product->id);
      $product->description = $productUpdateRequest->description;
      $product->condition = $productUpdateRequest->condition;
      $product->updated_by = 1;
      $product->save();

      if (!$hasVariations) {
        // product title has changed, assuming the details also change, therefore remove current root price
        if ($prevSlug != $product->slug) {
          ProductVariation::whereProductId($product->id)
            ->whereSku($prevSlug)
            ->delete();
        }

        ProductVariation::updateOrCreate([
          'sku' => $product->slug,
          'product_id' => $product->id,
        ], [
          'price' => $productUpdateRequest->price,
          'stock' => $productUpdateRequest->stock,
        ]);
      }

      if ($hasVariations) {
        // remove root price (this when product does not have variation)
        ProductVariation::whereProductId($product->id)
          ->whereSku($prevSlug)
          ->delete();

        $this->saveProductVariations($product, $productUpdateRequest);
      }

      if ($productUpdateRequest->image_urls) {
        foreach($productUpdateRequest->image_urls as $imageUrl) {
          $path = Image::saveImageFromUrl($imageUrl, "products/{$product->id}");
          if ($path) {
            ProductImage::create([
              'product_id' => $product->id,
              'path' => $path,
            ]);
          }
        }
      }

      if ($productUpdateRequest->deleted_images) {
        foreach($productUpdateRequest->deleted_images as $imageId) {
          $image = ProductImage::find($imageId);
          if ($image) {
            $image->delete();
          }
        }
      }

      $response = response([
        'success' => true,
        'message' => 'Tersimpan',
        'data' => [
          'product_id' => $product->id,
        ],
      ]);
    });

    return $response;
  }

  public function destroy(Product $product, DestroyProductRequest $request) {
    $hasOrder = OrderDetail::whereProductId($product->id)->count();
    if ($hasOrder) {
      return response([
        'success' => false,
        'message' => 'Tidak dapat menghapus produk, hanya dapat me-non-aktifkan produk ini'
      ], 400);
    }

    ProductVariation::whereProductId($product->id)->delete();

    foreach($product->images as $image) {
      $image->delete();
    }

    $product->delete();

    return response([
      'success' => true,
      'message' => 'Dihapus'
    ]);
  }

  public function toggleActive($productId) {
    $data = request()->validate([
      'is_active' => 'required|boolean'
    ]);

    $product = Product::withoutGlobalScopes()->findOrFail($productId);

    $stock = ProductVariation::select('stock')
      ->whereProductId($product->id)
      ->sum('stock');

    if (!$stock) {
      return response([
        'success' => false,
        'message' => 'Tidak dapat mengaktifkan produk karena stok kosong'
      ], 400);
    }

    $product->is_active = $data['is_active'];
    $product->save();

    return response([
      'success' => true,
      'message' => "Produk telah " . ($product->is_active ? 'aktif' : 'di nonaktifkan')
    ]);
  }

  public function getProductVariationByOptions($productId) {
    $variationOptionId = explode(',', request('variation_option_id'));
    $count = count($variationOptionId);

    $productVariation = ProductVariation::select('product_variation_id')
      ->join('product_variation_options', 'product_variation_options.product_variation_id', '=', 'product_variations.id')
      ->where('product_id', $productId)
      ->whereIn('variation_option_id', $variationOptionId)
      ->groupBy('product_variation_id')
      ->havingRaw('COUNT(*) = ?', [$count])
      ->havingRaw('SUM(variation_option_id IN (' . implode(',', $variationOptionId) . ')) = ?', [$count])
      ->first();

    if (!$productVariation) {
      return response([], 404);
    }

    return ProductVariation::find($productVariation->product_variation_id);
  }

  public function saveProductsFromJSON() {
    $filePath = $this->saveImportFile();
    $file = Storage::get($filePath);
    $productsRequest = json_decode($file);

    $response = [
      'success' => false,
      'message' => 'Unexpected Error',
    ];

    $store = Shop::first();
    if (!$store) {
      if (!isset($productsRequest->store)) {
        $store = Shop::firstOrCreate([
          'name' => 'Your Store',
          'description' => 'Put your description here',
          'image_path' => 'store/dummy.png'
        ]);
      } else {
        $path = Image::saveImageFromUrl($productsRequest->store->imageUrl, 'store');
        $store = Shop::create([
          'name' => $productsRequest->store->name,
          'description' => Str::limit(strip_tags($productsRequest->store->meta->description), 255),
          'image_path' => $path,
        ]);
      }
    }

    DB::transaction(function() use ($productsRequest, &$response, $store){
      $productIds = [];
      foreach($productsRequest->data as $index => $productRequest) {
        $product = Product::create([
          'store_id' => $store->id,
          'name' => $productRequest->name,
          'slug' => Utils::slug(Product::class, $productRequest->name),
          'review_avg' => $productRequest->ratingAvg ?? 0,
          'sold_count' => $productRequest->soldCount ?? 0,
          'source' => $productRequest->url,
          'created_by' => Auth::id(),
          'is_active' => false,
        ]);

        ProductVariation::create([
          'product_id' => $product->id,
          'sku' => Str::slug($productRequest->name),
          'price' => $productRequest->normalPrice,
          'discount_price' => $productRequest->discountPrice,
          'weight' => 500,
        ]);

        if ($index < 15) {
          $savePath = "products/{$product->id}";
          $path = Image::saveImageFromUrl($productRequest->imageUrl, savePath: $savePath);

          ProductImage::create([
            'product_id' => $product->id,
            'path' => $path,
          ]);
        } else {
          // will download the image later by the scheduler
          ProductImage::create([
            'product_id' => $product->id,
            'path' => $productRequest->imageUrl,
          ]);
        }

        $productIds[] = $product->id;
      }

      $response = [
        'success' => true,
        'message' => 'Success',
        'data' => [
          'product_ids' => $productIds,
        ]
      ];
    });

    return response($response, $response['success'] ? 200 : 500);
  }

  public function saveProductDetailFromJSON() {
    $file = Storage::disk('local')->get('shopee_product_detail.json');
    $productRequest = json_decode($file);

    $response = null;
    DB::transaction(function() use ($productRequest, &$response) {
      $source = Utils::getDomainFromUrl($productRequest->origin);

      $product = new Product;
      $product->store_id = 1;
      $product->name = substr($productRequest->name, 0, 191);
      $product->slug = Str::slug($productRequest->name);
      $product->description = strip_tags($productRequest->description);
      $product->review_avg = $productRequest->reviewAvg;
      $product->review_count = $productRequest->reviewCount;
      $product->sold_count = $productRequest->soldCount;
      $product->created_by = Auth::id();
      $product->save();

      $activeSelection = [];
      $optionGroups = []; // used for combinations
      foreach($productRequest->variants as $variant) {
        $attribute = VariationAttribute::firstOrCreate([
          'name' => $variant->name,
        ]);

        $options = [];
        foreach($variant->options as $option) {
          $variationOption = VariationOption::firstOrCreate([
            'variation_attribute_id' => $attribute->id,
            'value' => $option->name,
          ]);

          if ($source == 'tokopedia' && $option->status == "selected") {
            $activeSelection[] = $variationOption->id;
          } else if ($source == 'shopee' && $option->isSelected) {
            $activeSelection[] = $variationOption->id;
          }

          $options[] = $variationOption;
        }

        $optionGroups[] = $options;
      }

      // Generate all variant combinations
      $combinations = [[]];
      foreach ($optionGroups as $group) {
        $tmp = [];
        foreach ($combinations as $combo) {
          foreach ($group as $option) {
            $tmp[] = array_merge($combo, [$option]);
          }
        }
        $combinations = $tmp;
      }

      // Insert product variations and map to options
      foreach ($combinations as $optionCombo) {
        $sku = implode('-', array_map(fn($item) => Str::slug($item->value), $optionCombo));

        // basically says, check if this combination is the same as json data (product detail data)
        $isActiveSelectionCombo = array_map(fn($item) => $item->id, $optionCombo) == $activeSelection;

        if ($isActiveSelectionCombo) {
          $productVariation = ProductVariation::create([
            'product_id' => $product->id,
            'price' => $productRequest->originalPrice,
            'discount_price' => $productRequest->price < $productRequest->originalPrice ? $productRequest->price : null,
            'stock' => $productRequest->stock,
            'sku' => $sku,
          ]);
        } else {
          $productVariation = ProductVariation::create([
            'product_id' => $product->id,
            'price' => 0,
            'discount_price' => null,
            'stock' => 0,
            'sku' => $sku,
          ]);
        }

        foreach ($optionCombo as $opt) {
          ProductVariationOption::create([
            'product_variation_id' => $productVariation->id,
            'variation_option_id' => $opt->id,
          ]);
        }
      }


      if (request('import_image')) {
        $images = [];
        foreach($productRequest->images as $image) {
          $savePath = "products/{$product->id}";
          $path = Image::saveImageFromUrl($image->image500, savePath: $savePath);

          if ($path) {
            $images[] = [
              'product_id' => $product->id,
              'path' => $path,
              'alt' => substr($image->alt, 0, 191),
              'created_at' => now(),
              'updated_at' => now(),
            ];
          }
        }

        ProductImage::insert($images);
      }

      $response = $product;
    });

    return response($response);
  }

  private function saveImportFile() {
    $file = request()->file('file');
    $ext = $file->getClientOriginalExtension();
    $filename = Str::uuid() . ".{$ext}";
    return $file->storeAs('import', $filename);
  }

  private function saveProductVariations(Product $product, $productUpdateRequest) {
    // insert variation attributes
    $attributesWithId = [];
    foreach($productUpdateRequest->variations[0]['attributes'] as $attributeWithValue) {
      $attributeName = array_keys($attributeWithValue)[0];
      $variationAttribute = VariationAttribute::updateOrCreate([
        'name' => $attributeName
      ]);

      $attributesWithId[$attributeName] = $variationAttribute->id;
    }

    foreach($productUpdateRequest->variations as $variation) {
      $productVariation = ProductVariation::updateOrCreate([
        'sku' => $variation['sku'],
        'product_id' => $product->id,
      ], [
        'price' => $variation['price'],
        'stock' => $variation['stock'],
        'weight' => $variation['weight'],
      ]);

      foreach($variation['attributes'] as $attributeWithValue) {
        $attributeName = array_keys($attributeWithValue)[0];
        $attributeValue = array_values($attributeWithValue)[0];

        $variationOption = VariationOption::updateOrCreate([
          'variation_attribute_id' => $attributesWithId[$attributeName],
          'value' => $attributeValue,
        ]);

        ProductVariationOption::updateOrCreate([
          'product_variation_id' => $productVariation->id,
          'variation_option_id' => $variationOption->id,
        ]);
      }
    }
  }

  public function bulkDiscount(BulkDiscountRequest $request) {
    DB::transaction(function() use ($request) {
      $productVariations = ProductVariation::whereIn('product_id', $request->product_ids)->get();

      foreach($productVariations as $variation) {
        $discountPrice = 0;
        if ($request->discount_type == 'fixed') {
          $discountPrice = $variation->price - $request->discount_value;
        } else if ($request->discount_type == 'percentage') {
          $discountPrice = $variation->price - ($variation->price * ($request->discount_value / 100));
        }

        // Ensure discount price is not negative or zero if you want (but usually it shouldn't be negative)
        // If discount price is greater than normal price, it's weird, but formula above handles it (it would be negative).
        // Let's cap at 0.
        if ($discountPrice < 0) $discountPrice = 0;

        $variation->discount_price = $discountPrice;
        $variation->save();
      }
    });

    return response([
      'success' => true,
      'message' => 'Diskon berhasil diterapkan'
    ]);
  }

  public function bulkStock(BulkStockRequest $request) {
    DB::transaction(function() use ($request) {
      $productVariations = ProductVariation::whereIn('product_id', $request->product_ids)->get();

      foreach($productVariations as $variation) {
        $newStock = 0;
        
        if ($request->stock_action == 'set') {
          // Set stock to specific value
          $newStock = $request->stock_value;
        } else if ($request->stock_action == 'add') {
          // Add to current stock
          $newStock = $variation->stock + $request->stock_value;
        } else if ($request->stock_action == 'subtract') {
          // Subtract from current stock
          $newStock = $variation->stock - $request->stock_value;
          // Ensure stock doesn't go negative
          if ($newStock < 0) $newStock = 0;
        }

        $variation->stock = $newStock;
        $variation->save();
      }
    });

    return response([
      'success' => true,
      'message' => 'Stok berhasil diperbarui'
    ]);
  }

}
