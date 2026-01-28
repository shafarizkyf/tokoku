<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CheckoutSession;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckoutScopedController extends Controller
{
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
        libxml_use_internal_errors(true);
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

            $tdVillage = $td[2];
            $tdProvince = $td[count($td) - 1];

            $isSameProvince = $tdProvince == $village->district->regency->province->name;
            $isSameName = strtolower($tdVillage) == strtolower($village->name);

            if ($isSameName && $isSameProvince) {
                $tr[] = array_slice($td, 1);
            }
        }

        return $tr;
    }

    public function paymentChannels(Request $request)
    {
        $channels = \App\Helpers\Tripay::paymentChannels();
        return response()->json($channels['data'] ?? []);
    }
}
