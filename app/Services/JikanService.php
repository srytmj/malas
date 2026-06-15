<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class JikanService
{
    private const BASE_URL = 'https://api.jikan.moe/v4';

    public function getManga(int $malId): array
    {
        $response = Http::timeout(15)
            ->retry(2, 1000)
            ->get(self::BASE_URL . "/manga/{$malId}");

        if ($response->status() === 404) {
            throw new \Exception("Manga dengan MAL ID {$malId} tidak ditemukan di MyAnimeList.");
        }

        if (! $response->successful()) {
            throw new \Exception("Jikan API error: HTTP {$response->status()}");
        }

        return $response->json('data') ?? [];
    }

    /**
     * Search manga with optional filters.
     *
     * Supported filter keys: type, status, order_by, sort, min_score, max_score,
     * start_date (YYYY-MM-DD), end_date (YYYY-MM-DD), genres (comma-separated IDs)
     *
     * @see https://docs.api.jikan.moe/#tag/manga/operation/getMangaSearch
     * @return array{data: array, pagination: array}
     */
    public function searchManga(string $query, int $page = 1, array $filters = []): array
    {
        $params = ['q' => $query, 'limit' => 25, 'page' => $page];

        foreach (['type', 'status', 'order_by', 'sort', 'min_score', 'max_score', 'start_date', 'end_date', 'genres'] as $key) {
            if (! empty($filters[$key])) {
                $params[$key] = $filters[$key];
            }
        }

        if (empty($params['order_by'])) {
            $params['order_by'] = 'popularity';
            $params['sort'] = 'asc';
        }

        $response = Http::timeout(15)
            ->retry(2, 1000)
            ->get(self::BASE_URL . '/manga', $params);

        if (! $response->successful()) {
            throw new \Exception("Jikan API error: HTTP {$response->status()}");
        }

        return [
            'data'       => $response->json('data') ?? [],
            'pagination' => $response->json('pagination') ?? [],
        ];
    }

    /**
     * Browse manga by publication year with optional filters.
     *
     * @see https://docs.api.jikan.moe/#tag/manga/operation/getMangaSearch
     * @return array{data: array, pagination: array}
     */
    public function getMangaByYear(int $year, int $page = 1, array $filters = []): array
    {
        $params = [
            'start_date' => "{$year}-01-01",
            'end_date'   => "{$year}-12-31",
            'limit'      => 25,
            'page'       => $page,
            'order_by'   => $filters['order_by'] ?? 'start_date',
            'sort'       => $filters['sort'] ?? 'asc',
        ];

        foreach (['type', 'status'] as $key) {
            if (! empty($filters[$key])) {
                $params[$key] = $filters[$key];
            }
        }

        $response = Http::timeout(15)
            ->retry(2, 1000)
            ->get(self::BASE_URL . '/manga', $params);

        if (! $response->successful()) {
            throw new \Exception("Jikan API error: HTTP {$response->status()}");
        }

        return [
            'data'       => $response->json('data') ?? [],
            'pagination' => $response->json('pagination') ?? [],
        ];
    }

    public function downloadCover(string $url, string $filename): ?string
    {
        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $path = 'covers/' . $filename . '.' . $extension;

            Storage::disk(config('filesystems.cover_disk', 'public'))->put($path, $response->body());

            return $path;
        } catch (ConnectionException) {
            return null;
        }
    }
}
