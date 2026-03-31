<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Proxy zu Photon (OpenStreetMap) für PLZ/Ort/Straße — bitte fair nutzen (Throttle).
 */
class AddressSuggestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:120',
            'type' => 'sometimes|in:postcode,street',
            'city' => 'nullable|string|max:120',
        ]);

        $q = trim($validated['q']);
        $type = $validated['type'] ?? 'postcode';
        $city = isset($validated['city']) ? trim((string) $validated['city']) : '';

        if ($type === 'street' && $city !== '') {
            $q = $city.' '.$q;
        }

        try {
            $response = Http::timeout(6)
                ->withHeaders([
                    'User-Agent' => config('app.name').' (address-suggest; '.parse_url((string) config('app.url'), PHP_URL_HOST).')',
                ])
                ->get('https://photon.komoot.io/api/', [
                    'q' => $q,
                    'limit' => 15,
                    'lang' => 'de',
                ]);
        } catch (\Throwable) {
            return response()->json(['suggestions' => []]);
        }

        if (! $response->successful()) {
            return response()->json(['suggestions' => []]);
        }

        $features = $response->json('features') ?? [];
        $suggestions = [];

        foreach ($features as $feature) {
            $props = $feature['properties'] ?? [];
            $country = strtolower((string) ($props['countrycode'] ?? ''));
            if ($country !== '' && $country !== 'de') {
                continue;
            }

            $postcode = trim((string) ($props['postcode'] ?? ''));
            $cityName = trim((string) ($props['city'] ?? ''));
            $name = trim((string) ($props['name'] ?? ''));
            $street = trim((string) ($props['street'] ?? ''));
            $housenumber = trim((string) ($props['housenumber'] ?? ''));

            if ($type === 'postcode') {
                $pc = $postcode !== '' ? $postcode : (preg_match('/^\d{4,5}$/', $q) ? $q : '');
                $town = $cityName !== '' ? $cityName : $name;
                if ($town === '' && $pc === '') {
                    continue;
                }
                $label = trim($pc.' '.$town);
                $suggestions[] = [
                    'label' => $label,
                    'postcode' => $pc,
                    'city' => $town,
                ];
            } else {
                $line = trim($street !== '' ? $street.($housenumber !== '' ? ' '.$housenumber : '') : $name);
                if ($line === '') {
                    continue;
                }
                $suggestions[] = [
                    'label' => $line,
                    'street' => $line,
                ];
            }
        }

        $uniq = [];
        foreach ($suggestions as $s) {
            $key = $s['label'];
            if (! isset($uniq[$key])) {
                $uniq[$key] = $s;
            }
        }

        return response()->json(['suggestions' => array_values(array_slice($uniq, 0, 8))]);
    }
}
