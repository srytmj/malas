<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class AniListService
{
    private const BASE_URL = 'https://graphql.anilist.co';

    private const MEDIA_FIELDS = <<<'GRAPHQL'
        id
        title { romaji english native }
        format
        status
        countryOfOrigin
        volumes
        averageScore
        description(asHtml: false)
        genres
        coverImage { large }
        startDate { year month day }
        endDate { year month day }
        staff(perPage: 10) {
            edges { role node { name { full } } }
        }
        tags {
            name
            category
            rank
            isMediaSpoiler
        }
    GRAPHQL;

    public function __construct(private StorageSettingsService $storage) {}

    public function searchManga(string $query, int $page = 1): array
    {
        $gql = <<<GRAPHQL
            query (\$search: String, \$page: Int) {
                Page(page: \$page, perPage: 24) {
                    pageInfo { currentPage hasNextPage lastPage }
                    media(search: \$search, type: MANGA, sort: SEARCH_MATCH) {
                        {$this->mediaFields()}
                    }
                }
            }
        GRAPHQL;

        $data = $this->request($gql, ['search' => $query, 'page' => $page]);

        return [
            'data' => $data['Page']['media'] ?? [],
            'pagination' => $data['Page']['pageInfo'] ?? [],
        ];
    }

    public function getManga(int $anilistId): array
    {
        $gql = <<<GRAPHQL
            query (\$id: Int) {
                Media(id: \$id, type: MANGA) {
                    {$this->mediaFields()}
                }
            }
        GRAPHQL;

        $data = $this->request($gql, ['id' => $anilistId]);

        if (empty($data['Media'])) {
            throw new \Exception("Manga dengan AniList ID {$anilistId} tidak ditemukan.");
        }

        return $data['Media'];
    }

    public function ping(): array
    {
        $start = microtime(true);

        try {
            $response = Http::timeout(10)->post(self::BASE_URL, [
                'query' => 'query { Media(id: 1, type: MANGA) { id } }',
            ]);

            return [
                'online' => $response->successful(),
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'status' => $response->status(),
            ];
        } catch (ConnectionException|RequestException $e) {
            return [
                'online' => false,
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'status' => null,
            ];
        }
    }

    private function mediaFields(): string
    {
        return self::MEDIA_FIELDS;
    }

    private function request(string $query, array $variables = []): array
    {
        try {
            $response = Http::timeout(15)->retry(2, 1000)->post(self::BASE_URL, [
                'query' => $query,
                'variables' => $variables,
            ]);
        } catch (ConnectionException) {
            throw new \Exception('Tidak dapat terhubung ke AniList API. Periksa koneksi internet.');
        } catch (RequestException $e) {
            throw new \Exception($this->friendlyError($e->response->status()));
        }

        if (! $response->successful()) {
            throw new \Exception($this->friendlyError($response->status()));
        }

        $json = $response->json();

        if (! empty($json['errors'])) {
            $message = $json['errors'][0]['message'] ?? 'Unknown error';
            throw new \Exception("AniList API error: {$message}");
        }

        return $json['data'] ?? [];
    }

    private function friendlyError(int $status): string
    {
        return match (true) {
            $status === 404 => 'Data tidak ditemukan di AniList.',
            $status === 429 => 'Terlalu banyak request ke AniList API. Tunggu beberapa detik lalu coba lagi.',
            $status >= 500 => 'AniList sedang bermasalah atau tidak dapat diakses. Coba lagi beberapa saat.',
            default => "AniList API mengembalikan error HTTP {$status}.",
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

            return $this->storage->storeContents('covers', $filename.'.'.$extension, $response->body());
        } catch (ConnectionException) {
            return null;
        }
    }
}
