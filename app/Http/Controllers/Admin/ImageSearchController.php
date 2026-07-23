<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Services\AniListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImageSearchController extends Controller
{
    public function __construct(private AniListService $anilist) {}

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
            $raw = $this->anilist->searchManga($query, 1);
            $results = [];

            foreach ($raw['data'] as $item) {
                $image = $item['coverImage']['large'] ?? null;

                if (! $image) {
                    continue;
                }

                $results[] = [
                    'thumbnail' => $image,
                    'image' => $image,
                    'title' => $item['title']['romaji'] ?? '',
                ];
            }

            return response()->json(['results' => $results, 'error' => null]);
        } catch (\Exception $e) {
            return response()->json(['results' => [], 'error' => $e->getMessage()]);
        }
    }
}
