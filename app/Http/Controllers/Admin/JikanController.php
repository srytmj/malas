<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Services\JikanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JikanController extends Controller
{
    public function __construct(private JikanService $jikan) {}

    public function index(Request $request): Response
    {
        $this->authorize('create', Series::class);

        $results    = [];
        $pagination = [];
        $error      = null;

        if ($q = $request->get('q')) {
            try {
                $raw        = $this->jikan->searchManga(trim($q), (int) $request->get('page', 1));
                $results    = $this->formatResults($raw['data']);
                $pagination = $raw['pagination'];
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        return Inertia::render('Admin/Jikan/Index', [
            'results'    => $results,
            'pagination' => $pagination,
            'filters'    => ['q' => $request->get('q', '')],
            'error'      => $error,
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Series::class);

        $request->validate([
            'mal_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $data = $this->jikan->getManga((int) $request->mal_id);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $attributes = $this->mapToSeries($data);

        $existing = Series::where('mal_id', $data['mal_id'])->first();

        if ($existing) {
            // Tidak overwrite cover jika sudah ada
            if ($existing->cover_path) {
                unset($attributes['cover_path']);
            }
            $existing->update($attributes);
            $series = $existing;
            $message = 'Series berhasil diperbarui dari MyAnimeList.';
        } else {
            // Download cover
            $coverUrl = $data['images']['jpg']['large_image_url']
                ?? $data['images']['jpg']['image_url']
                ?? null;

            if ($coverUrl) {
                $attributes['cover_path'] = $this->jikan->downloadCover(
                    $coverUrl,
                    'mal_' . $data['mal_id']
                );
            }

            $series = Series::create($attributes);
            $message = 'Series berhasil diimpor dari MyAnimeList.';
        }

        return redirect()->route('admin.series.show', $series)
            ->with('success', $message);
    }

    private function mapToSeries(array $data): array
    {
        // Cari judul Jepang dari array titles
        $japaneseTitle = null;
        foreach ($data['titles'] ?? [] as $t) {
            if ($t['type'] === 'Japanese') {
                $japaneseTitle = $t['title'];
                break;
            }
        }

        return [
            'mal_id'         => $data['mal_id'],
            'title_romaji'   => $data['title'] ?? '',
            'title_english'  => $data['title_english'] ?: null,
            'title_japanese' => $japaneseTitle,
            'synopsis'       => $data['synopsis'] ?: null,
            'status'         => $this->mapStatus($data['status'] ?? ''),
            'type'           => $this->mapType($data['type'] ?? ''),
            'published_from' => $data['published']['from']
                ? substr($data['published']['from'], 0, 10)
                : null,
            'published_to'   => $data['published']['to']
                ? substr($data['published']['to'], 0, 10)
                : null,
            'total_volumes'  => $data['volumes'] ?: null,
            'score'          => $data['score'] ?: null,
            'rank'           => $data['rank'] ?: null,
        ];
    }

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'Publishing'         => 'publishing',
            'Finished'           => 'finished',
            'On Hiatus'          => 'on_hiatus',
            'Discontinued'       => 'discontinued',
            'Not yet published'  => 'not_yet_published',
            default              => 'publishing',
        };
    }

    private function mapType(string $type): string
    {
        return match ($type) {
            'Manhwa'                    => 'manhwa',
            'Manhua'                    => 'manhua',
            'Novel', 'Light Novel'      => 'novel',
            'One-shot'                  => 'one_shot',
            'Doujinshi'                 => 'doujinshi',
            default                     => 'manga',
        };
    }

    /** @param array<int, array<string, mixed>> $data */
    private function formatResults(array $data): array
    {
        return array_map(fn ($item) => [
            'mal_id'         => $item['mal_id'],
            'title'          => $item['title'],
            'title_english'  => $item['title_english'] ?: null,
            'cover_url'      => $item['images']['jpg']['image_url'] ?? null,
            'type'           => $item['type'] ?? null,
            'status'         => $item['status'] ?? null,
            'volumes'        => $item['volumes'] ?? null,
            'score'          => $item['score'] ?? null,
            'synopsis'       => $item['synopsis'] ?? null,
            'published_from' => isset($item['published']['from'])
                ? substr($item['published']['from'], 0, 10)
                : null,
            'already_imported' => Series::where('mal_id', $item['mal_id'])->exists(),
        ], $data);
    }
}
