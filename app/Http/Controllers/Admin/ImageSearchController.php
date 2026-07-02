<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Services\JikanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImageSearchController extends Controller
{
    public function __construct(private JikanService $jikan) {}

    public function search(Request $request): JsonResponse
    {
        $this->authorize('create', Series::class);

        $q = trim((string) $request->get('q', ''));

        if (! $q) {
            return response()->json(['results' => [], 'error' => null]);
        }

        // Strip common prefixes so "manga cover Berserk" → "Berserk"
        $query = preg_replace('/^(manga|manhwa|manhua|novel|anime)\s+(cover\s+)?/i', '', $q);
        $query = $query ?: $q;

        try {
            $raw     = $this->jikan->searchManga($query, 1);
            $results = [];

            foreach ($raw['data'] as $item) {
                $large  = $item['images']['jpg']['large_image_url'] ?? null;
                $normal = $item['images']['jpg']['image_url']       ?? null;
                $thumb  = $item['images']['jpg']['small_image_url'] ?? $normal;

                if (! $normal) continue;

                $results[] = [
                    'thumbnail' => $thumb,
                    'image'     => $large ?? $normal,
                    'title'     => $item['title'] ?? '',
                ];
            }

            return response()->json(['results' => $results, 'error' => null]);
        } catch (\Exception $e) {
            return response()->json(['results' => [], 'error' => $e->getMessage()]);
        }
    }
}
