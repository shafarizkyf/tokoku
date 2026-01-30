<?php

namespace App\Http\Controllers\API;

use App\Helpers\PostalCodeHelper;
use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Http\Request;

class CheckoutScopedController extends Controller
{
    use PostalCodeHelper;

    public function provinces(Request $request)
    {
        return Province::all();
    }

    public function regencies(Request $request, $sessionToken, Province $province)
    {
        return $province->regencies;
    }

    public function districts(Request $request, $sessionToken, Province $province, Regency $regency)
    {
        return $regency->districts;
    }

    public function villages(Request $request, $sessionToken, Province $province, Regency $regency, District $district)
    {
        return $district->villages;
    }

    public function postalCode(Request $request, $sessionToken, Village $village)
    {
        $results = self::fetchPostalCode($village);

        return response()->json($results);
    }

    public function paymentChannels(Request $request)
    {
        $channels = \App\Helpers\Tripay::paymentChannels();

        return response()->json($channels['data'] ?? []);
    }
}
