<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Services\JikanService;
use Illuminate\Http\JsonResponse;
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

    public function pictures(Request $request): JsonResponse
    {
        $this->authorize('create', Series::class);

        $malId = (int) $request->get('mal_id', 0);

        if (! $malId) {
            return response()->json(['results' => [], 'error' => null]);
        }

        try {
            $raw     = $this->jikan->getMangaPictures($malId);
            $results = [];

            foreach ($raw as $item) {
                $large  = $item['jpg']['large_image_url'] ?? $item['webp']['large_image_url'] ?? null;
                $normal = $item['jpg']['image_url']       ?? $item['webp']['image_url']       ?? null;
                $thumb  = $item['jpg']['small_image_url'] ?? $item['webp']['small_image_url'] ?? $normal;

                if (! $normal) continue;

                $results[] = [
                    'thumbnail' => $thumb,
                    'image'     => $large ?? $normal,
                    'title'     => '',
                ];
            }

            return response()->json(['results' => $results, 'error' => null]);
        } catch (\Exception $e) {
            return response()->json(['results' => [], 'error' => $e->getMessage()]);
        }
    }

    public function searchJson(Request $request): JsonResponse
    {
        $this->authorize('create', Series::class);

        $q = trim((string) $request->get('q', ''));

        if (! $q) {
            return response()->json(['results' => [], 'error' => null]);
        }

        try {
            $raw = $this->jikan->searchManga($q, 1);

            return response()->json([
                'results' => $this->formatResults($raw['data']),
                'error'   => null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['results' => [], 'error' => $e->getMessage()]);
        }
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Series::class);

        $request->validate([
            'mal_id'         => ['required', 'integer', 'min:1'],
            'title'          => ['nullable', 'string', 'max:500'],
            'title_english'  => ['nullable', 'string', 'max:500'],
            'cover_url'      => ['nullable', 'url'],
            'type'           => ['nullable', 'string'],
            'status'         => ['nullable', 'string'],
            'volumes'        => ['nullable', 'integer'],
            'score'          => ['nullable', 'numeric'],
            'synopsis'       => ['nullable', 'string'],
            'published_from' => ['nullable', 'string'],
        ]);

        $malId = (int) $request->mal_id;

        // Coba ambil data lengkap dari Jikan; fallback ke data search jika gagal
        $coverUrl    = null;
        $fromCache   = false;

        try {
            $data       = $this->jikan->getManga($malId);
            $attributes = $this->mapToSeries($data);
            $coverUrl   = $data['images']['jpg']['large_image_url']
                ?? $data['images']['jpg']['image_url']
                ?? null;
        } catch (\Exception $e) {
            if (! $request->filled('title')) {
                return redirect()->back()->with('error', $e->getMessage());
            }
            $attributes = $this->mapFromRequest($request);
            $coverUrl   = $request->cover_url;
            $fromCache  = true;
        }

        $existing = Series::where('mal_id', $malId)->first();

        if ($existing) {
            if ($existing->cover_path) {
                unset($attributes['cover_path']);
            }
            $existing->update($attributes);
            $series  = $existing;
            $message = 'Series berhasil diperbarui dari MyAnimeList.';
        } else {
            if ($coverUrl) {
                $attributes['cover_path'] = $this->jikan->downloadCover(
                    $coverUrl,
                    'mal_' . $malId
                );
            }
            $series  = Series::create($attributes);
            $message = $fromCache
                ? 'Series berhasil diimpor (data dari cache pencarian — beberapa field mungkin tidak lengkap).'
                : 'Series berhasil diimpor dari MyAnimeList.';
        }

        // Auto-generate volumes jika series selesai dan total_volumes diketahui
        $generated = $this->generateVolumesIfFinished($series);
        if ($generated > 0) {
            $message .= " {$generated} volume dibuat otomatis.";
        }

        return redirect()->route('admin.series.show', $series)
            ->with('success', $message);
    }

    private function mapFromRequest(Request $request): array
    {
        return [
            'mal_id'         => $request->mal_id,
            'title_romaji'   => $request->title ?? '',
            'title_english'  => $request->title_english ?: null,
            'title_japanese' => null,
            'synopsis'       => $request->synopsis ?: null,
            'status'         => $this->mapStatus($request->status ?? ''),
            'type'           => $this->mapType($request->type ?? ''),
            'published_from' => $request->published_from ?: null,
            'published_to'   => null,
            'total_volumes'  => $request->volumes ? (int) $request->volumes : null,
            'score'          => $request->score ? (float) $request->score : null,
            'rank'           => null,
        ];
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

    private function generateVolumesIfFinished(Series $series): int
    {
        if ($series->status !== 'finished' || ! $series->total_volumes) {
            return 0;
        }

        $existing = $series->volumes()->pluck('volume_number')->all();
        $created  = 0;

        for ($i = 1; $i <= $series->total_volumes; $i++) {
            if (! in_array($i, $existing)) {
                $series->volumes()->create(['volume_number' => $i, 'type' => 'regular']);
                $created++;
            }
        }

        return $created;
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
        $malIds   = array_column($data, 'mal_id');
        $imported = Series::whereIn('mal_id', $malIds)->pluck('mal_id')->flip()->toArray();

        return array_map(fn ($item) => [
            'mal_id'           => $item['mal_id'],
            'title'            => $item['title'],
            'title_english'    => $item['title_english'] ?: null,
            'cover_url'        => $item['images']['jpg']['image_url'] ?? null,
            'type'             => $item['type'] ?? null,
            'status'           => $item['status'] ?? null,
            'volumes'          => $item['volumes'] ?? null,
            'score'            => $item['score'] ?? null,
            'synopsis'         => $item['synopsis'] ?? null,
            'published_from'   => isset($item['published']['from'])
                ? substr($item['published']['from'], 0, 10)
                : null,
            'already_imported' => isset($imported[$item['mal_id']]),
        ], $data);
    }
}
