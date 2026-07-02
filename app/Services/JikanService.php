<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class JikanService
{
    private const BASE_URL = 'https://api.jikan.moe/v4';

    public function getManga(int $malId): array
    {
        $response = $this->request(self::BASE_URL . "/manga/{$malId}");

        if ($response->status() === 404) {
            throw new \Exception("Manga dengan MAL ID {$malId} tidak ditemukan di MyAnimeList.");
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

        $response = $this->request(self::BASE_URL . '/manga', $params);

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

        $response = $this->request(self::BASE_URL . '/manga', $params);

        return [
            'data'       => $response->json('data') ?? [],
            'pagination' => $response->json('pagination') ?? [],
        ];
    }

    public function getMangaFull(int $malId): array
    {
        $response = $this->request(self::BASE_URL . "/manga/{$malId}/full");

        if ($response->status() === 404) {
            throw new \Exception("Manga dengan MAL ID {$malId} tidak ditemukan di MyAnimeList.");
        }

        return $response->json('data') ?? [];
    }

    public function getMangaPictures(int $malId): array
    {
        try {
            $response = $this->request(self::BASE_URL . "/manga/{$malId}/pictures");
            return $response->json('data') ?? [];
        } catch (\Exception) {
            return [];
        }
    }

    private function request(string $url, array $params = []): \Illuminate\Http\Client\Response
    {
        try {
            $response = Http::timeout(15)->retry(2, 1000)->get($url, $params);
        } catch (ConnectionException) {
            throw new \Exception('Tidak dapat terhubung ke Jikan API. Periksa koneksi internet.');
        } catch (RequestException $e) {
            throw new \Exception($this->friendlyError($e->response->status()));
        }

        if (! $response->successful()) {
            throw new \Exception($this->friendlyError($response->status()));
        }

        return $response;
    }

    private function friendlyError(int $status): string
    {
        return match (true) {
            $status === 404 => 'Data tidak ditemukan di MyAnimeList.',
            $status === 429 => 'Terlalu banyak request ke Jikan API. Tunggu beberapa detik lalu coba lagi.',
            $status >= 500  => 'MyAnimeList sedang bermasalah atau tidak dapat diakses. Coba lagi beberapa saat.',
            default         => "Jikan API mengembalikan error HTTP {$status}.",
        };
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
