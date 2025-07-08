<?php

namespace App\Http\Controllers\API;

use App\Helpers\Image;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Models\ProductVariationOption;
use App\Models\VariationAttribute;
use App\Models\VariationOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller {

  public function store() {
    $file = Storage::disk('local')->get('sample.json');
    $productRequest = json_decode($file);

    $response = null;
    DB::transaction(function() use ($productRequest, &$response) {
      $product = new Product;
      $product->store_id = 1;
      $product->name = substr($productRequest->name, 0, 191);
      $product->slug = Str::slug($productRequest->name);
      $product->description = $productRequest->description;
      $product->review_avg = $productRequest->reviewAvg;
      $product->review_count = $productRequest->reviewCount;
      $product->created_by = 1;
      $product->save();

      $optionGroups = []; // used for combinations
      foreach($productRequest->variants as $variant) {
        $attribute = VariationAttribute::firstOrCreate([
          'name' => $variant->name,
        ]);

        $options = [];
        foreach($variant->options as $option) {
          if (!in_array($option->status, ['selected', 'active'])) continue;

          $variationOption = VariationOption::firstOrCreate([
            'variation_attribute_id' => $attribute->id,
            'value' => $option->name,
          ]);

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
        $productVariation = ProductVariation::create([
          'product_id' => $product->id,
          'price' => $productRequest->price,
          'stock' => $productRequest->stock,
          'sku' => $product->id . '-' . implode('-', array_map(fn($opt) => Str::slug($opt->value), $optionCombo)),
        ]);

        foreach ($optionCombo as $opt) {
          ProductVariationOption::create([
            'product_variation_id' => $productVariation->id,
            'variation_option_id' => $opt->id,
          ]);
        }
      }


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

      $response = $product;
    });

    return response($response);
  }

}
