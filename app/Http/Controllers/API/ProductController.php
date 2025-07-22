<?php

namespace App\Http\Controllers\API;

use App\Helpers\DataTable;
use App\Helpers\Image;
use App\Helpers\Utils;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Models\ProductVariationOption;
use App\Models\Shop;
use App\Models\VariationAttribute;
use App\Models\VariationOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller {

  public function index() {
    $products = Product::with(['image', 'variation']);

    if (request('view') == 'datatable') {
      return DataTable::ajaxTable($products);
    }

    return $products->paginate(30);
  }

  public function show(Product $product) {
    $product = $product->load('variations');
    $product['variation_options'] = ProductVariation::options()->where('product_id', $product->id)->get();

    return $product;
  }

  public function store(ProductUpdateRequest $productUpdateRequest) {
    Log::channel('product')->info('store', $productUpdateRequest->toArray());

    DB::transaction(function() use (&$productUpdateRequest) {
      $hasVariations = isset($productUpdateRequest->variations) && count($productUpdateRequest->variations);

      $product = new Product;
      $product->store_id = 1;
      $product->name = $productUpdateRequest->name;
      $product->slug = Utils::slug(Product::class, $productUpdateRequest->name);
      $product->description = $productUpdateRequest->description;
      $product->condition = $productUpdateRequest->condition;
      $product->created_by = 1;
      $product->save();

      if (!$hasVariations) {
        ProductVariation::create( [
          'sku' => $product->slug,
          'product_id' => $product->id,
          'price' => $productUpdateRequest->price,
          'stock' => $productUpdateRequest->stock,
        ]);
      } else {
        $this->saveProductVariations($product, $productUpdateRequest);
      }
    });

    return response([]);
  }

  public function update(Product $product, ProductUpdateRequest $productUpdateRequest) {
    Log::channel('product')->info('update', $productUpdateRequest->toArray());

    DB::transaction(function() use ($product, &$productUpdateRequest) {
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
    });

    return response([]);
  }

  public function saveProductsFromJSON() {
    $filePath = $this->saveImportFile();
    $file = Storage::get($filePath);
    $productsRequest = json_decode($file);

    $response = [
      'success' => false,
      'message' => 'Unexpected Error',
    ];

    DB::transaction(function() use ($productsRequest, &$response){
      $path = Image::saveImageFromUrl($productsRequest->store->imageUrl, 'store');

      $store = Shop::create([
        'name' => $productsRequest->store->name,
        'description' => $productsRequest->store->meta->description,
        'image_path' => $path,
      ]);

      $productIds = [];
      foreach($productsRequest->data as $index => $productRequest) {
        $product = Product::create([
          'store_id' => $store->id,
          'name' => $productRequest->name,
          'slug' => Str::slug($productRequest->name),
          'review_avg' => $productRequest->ratingAvg,
          'sold_count' => $productRequest->soldCount,
          'source' => $productRequest->url,
          'created_by' => 1,
        ]);

        ProductVariation::create([
          'product_id' => $product->id,
          'sku' => Str::slug($productRequest->name),
          'price' => $productRequest->normalPrice,
          'discount_price' => $productRequest->discountPrice,
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
      $product->created_by = 1;
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

}
