<?php

namespace App\Modules\Jikan;

use App\Modules\Core\Models\Series;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class JikanService
{
    private string $baseUrl = 'https://api.jikan.moe/v4';

    public function getMangaList(int $page = 1, ?int $startYear = null, ?int $endYear = null): array
    {
        $params = [
            'page'     => $page,
            'limit'    => 25,
            'type'     => 'manga',
            'order_by' => ($startYear || $endYear) ? 'start_date' : 'mal_id',
            'sort'     => 'asc',
        ];

        if ($startYear) {
            $params['start_date'] = "{$startYear}-01-01";
        }
        if ($endYear) {
            $params['end_date'] = "{$endYear}-12-31";
        }

        $response = Http::timeout(30)
            ->retry(3, 2000)
            ->get("{$this->baseUrl}/manga", $params);

        if ($response->failed()) {
            throw new \RuntimeException("Jikan API error {$response->status()} on page {$page}");
        }

        return $response->json();
    }

    public function upsertPage(array $data): array
    {
        $newCount     = 0;
        $updatedCount = 0;

        foreach ($data as $manga) {
            $mapped = $this->mapToSeries($manga);
            if (empty($mapped['title_romaji'])) continue;

            $imageUrl = $manga['images']['jpg']['large_image_url']
                ?? $manga['images']['jpg']['image_url']
                ?? null;

            $existing = Series::withTrashed()->where('mal_id', $manga['mal_id'])->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update($mapped);
                if ($imageUrl) {
                    $this->downloadCover($manga['mal_id'], $imageUrl, $existing);
                }
                $updatedCount++;
            } else {
                $series = Series::create($mapped);
                if ($imageUrl) {
                    $this->downloadCover($manga['mal_id'], $imageUrl, $series);
                }
                $newCount++;
            }
        }

        return [$newCount, $updatedCount];
    }

    private function downloadCover(int $malId, string $imageUrl, Series $series): void
    {
        try {
            $response = Http::timeout(15)->get($imageUrl);
            if ($response->failed()) return;

            $ext  = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg');
            $ext  = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'jpg';
            $path = "covers/series/{$malId}.{$ext}";

            Storage::put($path, $response->body());
            $series->update(['cover_image_path' => $path]);
        } catch (\Throwable) {
            // Cover download failure is non-fatal
        }
    }

    private function mapToSeries(array $manga): array
    {
        $publishedFrom = null;
        $publishedTo   = null;

        try {
            if (!empty($manga['published']['from'])) {
                $publishedFrom = Carbon::parse($manga['published']['from'])->toDateString();
            }
            if (!empty($manga['published']['to'])) {
                $publishedTo = Carbon::parse($manga['published']['to'])->toDateString();
            }
        } catch (\Throwable) {}

        return [
            'mal_id'         => $manga['mal_id'],
            'title_romaji'   => $manga['title'] ?? '',
            'title_english'  => $manga['title_english'] ?? null,
            'title_japanese' => $manga['title_japanese'] ?? null,
            'synopsis'       => $manga['synopsis'] ?? null,
            'status'         => $this->mapStatus($manga['status'] ?? ''),
            'total_volumes'  => $manga['volumes'] ?? null,
            'published_from' => $publishedFrom,
            'published_to'   => $publishedTo,
            'score'          => $manga['score'] ?? null,
            'rank'           => $manga['rank'] ?? null,
            'authors'        => collect($manga['authors'] ?? [])->pluck('name')->values()->all(),
            'genres'         => collect($manga['genres'] ?? [])->pluck('name')->values()->all(),
            'demographics'   => collect($manga['demographics'] ?? [])->pluck('name')->values()->all(),
            'last_synced_at' => now(),
        ];
    }

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'Publishing'   => 'publishing',
            'Finished'     => 'finished',
            'On Hiatus'    => 'on_hiatus',
            'Discontinued' => 'discontinued',
            default        => 'not_yet_published',
        };
    }
}
