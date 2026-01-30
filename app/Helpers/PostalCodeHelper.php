<?php

namespace App\Helpers;

use App\Models\Village;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait PostalCodeHelper
{
    public static function fetchPostalCode(Village $village): array
    {
        $response = Http::asForm()->post('https://kodepos.posindonesia.co.id/CariKodepos', [
            'kodepos' => $village->name,
        ]);

        if (!$response->successful()) {
            Log::error('postalCode: ' . $response->body());
            return [];
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($response->body());
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $rows = $xpath->query("//tbody/tr");

        $results = [];
        foreach ($rows as $row) {
            if (!($row instanceof \DOMElement)) {
                continue;
            }

            $cols = $row->getElementsByTagName("td");
            $td = [];
            foreach ($cols as $col) {
                $td[] = trim($col->textContent);
            }

            $tdVillage = $td[2] ?? '';
            $tdProvince = $td[count($td) - 1] ?? '';

            $isSameProvince = $tdProvince == $village->district->regency->province->name;
            $isSameName = strtolower($tdVillage) == strtolower($village->name);

            if ($isSameName && $isSameProvince) {
                $results[] = array_slice($td, 1);
            }
        }

        return $results;
    }
}
