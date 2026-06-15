<?php

namespace App\Actions;

use App\Models\Series;
use App\Models\Volume;
use App\Services\JikanService;
use Carbon\Carbon;

class ImportSeriesFromJikan
{
    public function __construct(private JikanService $jikan) {}

    /**
     * @throws \Exception
     */
    public function handle(int $malId): Series
    {
        $existing = Series::where('mal_id', $malId)->first();
        if ($existing) {
            throw new \Exception("Series '{$existing->title_romaji}' sudah ada (MAL ID: {$malId}).");
        }

        $data = $this->jikan->getManga($malId);

        $titles = collect($data['titles'] ?? []);
        $titleRomaji = $titles->firstWhere('type', 'Default')['title']
            ?? $data['title']
            ?? 'Unknown';
        $titleEnglish = $titles->firstWhere('type', 'English')['title'] ?? null;
        $titleJapanese = $titles->firstWhere('type', 'Japanese')['title'] ?? null;

        $status = match ($data['status'] ?? '') {
            'Publishing' => 'publishing',
            'Finished' => 'finished',
            'On Hiatus' => 'on_hiatus',
            'Discontinued' => 'discontinued',
            'Not yet published' => 'not_yet_published',
            default => 'publishing',
        };

        $publishedFrom = filled($data['published']['from'] ?? null)
            ? Carbon::parse($data['published']['from'])->toDateString()
            : null;
        $publishedTo = filled($data['published']['to'] ?? null)
            ? Carbon::parse($data['published']['to'])->toDateString()
            : null;

        $totalVolumes = $data['volumes'] ?? null;

        $series = Series::create([
            'mal_id' => $malId,
            'title_romaji' => $titleRomaji,
            'title_english' => $titleEnglish,
            'title_japanese' => $titleJapanese,
            'synopsis' => $data['synopsis'] ?? null,
            'status' => $status,
            'published_from' => $publishedFrom,
            'published_to' => $publishedTo,
            'total_volumes' => $totalVolumes,
            'score' => $data['score'] ?? null,
            'rank' => $data['rank'] ?? null,
        ]);

        // Auto-create volume placeholder records
        if ($totalVolumes && $totalVolumes > 0) {
            for ($i = 1; $i <= $totalVolumes; $i++) {
                Volume::firstOrCreate(
                    ['series_id' => $series->id, 'volume_number' => $i],
                );
            }
        }

        // Download cover
        $imageUrl = $data['images']['jpg']['large_image_url'] ?? null;
        if ($imageUrl) {
            $coverPath = $this->jikan->downloadCover($imageUrl, (string) $malId);
            if ($coverPath) {
                $series->update(['cover_path' => $coverPath]);
            }
        }

        return $series;
    }
}
