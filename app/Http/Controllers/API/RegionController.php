<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RegionController extends Controller {

  public function provinces() {
    return Cache::rememberForever('provinces', function(){
      return Province::all();
    });
  }

  public function regencies(Province $province) {
    return Cache::rememberForever("province.{$province->id}.regencies", function() use (&$province) {
      return $province->regencies;
    });
  }

  public function districts(Province $province, Regency $regency) {
    return Cache::rememberForever("regency.{$regency->id}.districts", function() use (&$regency) {
      return $regency->districts;
    });
  }

  public function villages(Province $province, Regency $regency, District $district) {
    return Cache::rememberForever("district.{$district->id}.villages", function() use (&$district) {
      return $district->villages;
    });
  }

  public function postalCode(Village $village) {
    $response = Http::asForm()->post('https://kodepos.posindonesia.co.id/CariKodepos', [
      'kodepos' => $village->name,
    ]);

    if (!$response->successful()) {
      Log::error('postalCode: ' . $response->body());
      return response([
        'message' => 'Unexpected Error'
      ], 500);
    }

    $dom = new \DOMDocument();
    libxml_use_internal_errors(true); // suppress HTML5 warnings
    $dom->loadHTML($response->body());
    libxml_clear_errors();

    $xpath = new \DOMXPath($dom);
    $rows = $xpath->query("//tbody/tr");

    $tr = [];
    foreach ($rows as $row) {
      if (!($row instanceof \DOMElement)) {
          continue;
      }

      $cols = $row->getElementsByTagName("td");
      $td = [];
      foreach ($cols as $col) {
        $td[] = trim($col->textContent);
      }

      $tr[] = array_slice($td, 1);
    }

    return $tr;
  }

}
