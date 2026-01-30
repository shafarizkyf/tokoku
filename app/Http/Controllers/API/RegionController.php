<?php

namespace App\Http\Controllers\API;

use App\Helpers\PostalCodeHelper;
use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Support\Facades\Cache;

class RegionController extends Controller
{
    use PostalCodeHelper;

    public function provinces()
    {
        return Cache::rememberForever('provinces', function () {
            return Province::all();
        });
    }

    public function regencies(Province $province)
    {
        return Cache::rememberForever("province.{$province->id}.regencies", function () use (&$province) {
            return $province->regencies;
        });
    }

    public function districts(Province $province, Regency $regency)
    {
        return Cache::rememberForever("regency.{$regency->id}.districts", function () use (&$regency) {
            return $regency->districts;
        });
    }

    public function villages(Province $province, Regency $regency, District $district)
    {
        return Cache::rememberForever("district.{$district->id}.villages", function () use (&$district) {
            return $district->villages;
        });
    }

    public function postalCode(Village $village)
    {
        $results = self::fetchPostalCode($village);

        return response()->json($results);
    }
}
