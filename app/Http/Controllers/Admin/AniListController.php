<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Services\AniListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AniListController extends Controller
{
    public function __construct(private AniListService $anilist) {}

    public function index(Request $request): Response
    {
        $this->authorize('create', Series::class);

        $results    = [];
        $pagination = [];
        $error      = null;

        if ($q = $request->get('q')) {
            try {
                $raw        = $this->anilist->searchManga(trim($q), (int) $request->get('page', 1));
                $results    = $this->formatResults($raw['data']);
                $pagination = $raw['pagination'];
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        return Inertia::render('Admin/AniList/Index', [
            'results'    => $results,
            'pagination' => $pagination,
            'filters'    => ['q' => $request->get('q', '')],
            'error'      => $error,
        ]);
    }

    public function searchJson(Request $request): JsonResponse
    {
        $this->authorize('create', Series::class);

        $q = trim((string) $request->get('q', ''));

        if (! $q) {
            return response()->json(['results' => [], 'error' => null]);
        }

        try {
            $raw = $this->anilist->searchManga($q, 1);

            return response()->json([
                'results' => $this->formatResults($raw['data']),
                'error'   => null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['results' => [], 'error' => $e->getMessage()]);
        }
    }

    public function statusPage(): Response
    {
        $this->authorize('create', Series::class);

        $recentImports = Series::whereNotNull('anilist_id')
            ->latest('updated_at')
            ->limit(20)
            ->get(['id', 'title_romaji', 'anilist_id', 'updated_at'])
            ->map(fn ($s) => [
                'id'           => $s->id,
                'title_romaji' => $s->title_romaji,
                'anilist_id'   => $s->anilist_id,
                'updated_at'   => $s->updated_at->toIso8601String(),
            ]);

        return Inertia::render('Admin/AniList/Status', [
            'recentImports' => $recentImports,
        ]);
    }

    public function statusCheck(): JsonResponse
    {
        $this->authorize('create', Series::class);

        return response()->json($this->anilist->ping());
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Series::class);

        $request->validate([
            'anilist_id'     => ['required', 'integer', 'min:1'],
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

        $anilistId = (int) $request->anilist_id;

        $coverUrl  = null;
        $fromCache = false;

        try {
            $data       = $this->anilist->getManga($anilistId);
            $attributes = $this->mapToSeries($data);
            $coverUrl   = $data['coverImage']['large'] ?? null;
        } catch (\Exception $e) {
            if (! $request->filled('title')) {
                return redirect()->back()->with('error', $e->getMessage());
            }
            $attributes = $this->mapFromRequest($request);
            $coverUrl   = $request->cover_url;
            $fromCache  = true;
        }

        $existing = Series::where('anilist_id', $anilistId)->first();

        if ($existing) {
            if ($existing->cover_path) {
                unset($attributes['cover_path']);
            }
            $existing->update($attributes);
            $series  = $existing;
            $message = 'Series berhasil diperbarui dari AniList.';
        } else {
            if ($coverUrl) {
                $attributes['cover_path'] = $this->anilist->downloadCover(
                    $coverUrl,
                    'anilist_' . $anilistId
                );
            }
            $series  = Series::create($attributes);
            $message = $fromCache
                ? 'Series berhasil diimpor (data dari cache pencarian — beberapa field mungkin tidak lengkap).'
                : 'Series berhasil diimpor dari AniList.';
        }

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
            'anilist_id'     => $request->anilist_id,
            'title_romaji'   => $request->title ?? '',
            'title_english'  => $request->title_english ?: null,
            'title_japanese' => null,
            'synopsis'       => $request->synopsis ?: null,
            'status'         => $request->status ?: 'publishing',
            'type'           => $request->type ?: 'manga',
            'published_from' => $request->published_from ?: null,
            'published_to'   => null,
            'total_volumes'  => $request->volumes ? (int) $request->volumes : null,
            'score'          => $request->score ? (float) $request->score : null,
        ];
    }

    private function mapToSeries(array $data): array
    {
        $tags = $data['tags'] ?? [];

        return [
            'anilist_id'     => $data['id'],
            'title_romaji'   => $data['title']['romaji'] ?? '',
            'title_english'  => $data['title']['english'] ?: null,
            'title_japanese' => $data['title']['native'] ?: null,
            'synopsis'       => $this->cleanDescription($data['description'] ?? null),
            'status'         => $this->mapStatus($data['status'] ?? ''),
            'type'           => $this->mapType($data['format'] ?? '', $data['countryOfOrigin'] ?? null),
            'published_from' => $this->buildDate($data['startDate'] ?? null),
            'published_to'   => $this->buildDate($data['endDate'] ?? null),
            'total_volumes'  => $data['volumes'] ?: null,
            'score'          => isset($data['averageScore']) ? round($data['averageScore'] / 10, 2) : null,
            'genres'         => $data['genres'] ?? [],
            'authors'        => $this->extractAuthors($data['staff']['edges'] ?? []),
            'themes'         => $this->extractTags($tags, 'Theme-', 8),
            'demographics'   => $this->extractTags($tags, 'Demographic', 5),
        ];
    }

    private function extractAuthors(array $staffEdges): array
    {
        $authors = [];

        foreach ($staffEdges as $edge) {
            $role = strtolower($edge['role'] ?? '');

            if (str_contains($role, 'assistant')) {
                continue;
            }

            if (str_contains($role, 'story') || str_contains($role, 'art')) {
                $name = $edge['node']['name']['full'] ?? null;
                if ($name) {
                    $authors[] = $name;
                }
            }
        }

        return array_values(array_unique($authors));
    }

    private function extractTags(array $tags, string $categoryPrefix, int $limit): array
    {
        $filtered = array_values(array_filter($tags, fn ($t) =>
            ! ($t['isMediaSpoiler'] ?? false)
            && str_starts_with($t['category'] ?? '', $categoryPrefix)
        ));

        usort($filtered, fn ($a, $b) => ($b['rank'] ?? 0) <=> ($a['rank'] ?? 0));

        return array_slice(array_column($filtered, 'name'), 0, $limit);
    }

    private function buildDate(?array $date): ?string
    {
        if (empty($date['year'])) {
            return null;
        }

        $month = str_pad((string) ($date['month'] ?? 1), 2, '0', STR_PAD_LEFT);
        $day   = str_pad((string) ($date['day'] ?? 1), 2, '0', STR_PAD_LEFT);

        return "{$date['year']}-{$month}-{$day}";
    }

    private function cleanDescription(?string $description): ?string
    {
        if (! $description) {
            return null;
        }

        $text = str_ireplace(['<br>', '<br/>', '<br />'], "\n", $description);
        $text = strip_tags($text);

        return trim($text) ?: null;
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
            'RELEASING'        => 'publishing',
            'FINISHED'         => 'finished',
            'HIATUS'           => 'on_hiatus',
            'CANCELLED'        => 'discontinued',
            'NOT_YET_RELEASED' => 'not_yet_published',
            default            => 'publishing',
        };
    }

    private function mapType(string $format, ?string $countryOfOrigin): string
    {
        return match (true) {
            $format === 'NOVEL'    => 'novel',
            $format === 'ONE_SHOT' => 'one_shot',
            $countryOfOrigin === 'KR' => 'manhwa',
            in_array($countryOfOrigin, ['CN', 'TW'], true) => 'manhua',
            default => 'manga',
        };
    }

    /** @param array<int, array<string, mixed>> $data */
    private function formatResults(array $data): array
    {
        $anilistIds = array_column($data, 'id');
        $imported   = Series::whereIn('anilist_id', $anilistIds)->pluck('anilist_id')->flip()->toArray();

        return array_map(fn ($item) => [
            'anilist_id'       => $item['id'],
            'title'            => $item['title']['romaji'] ?? '',
            'title_english'    => $item['title']['english'] ?? null,
            'cover_url'        => $item['coverImage']['large'] ?? null,
            'type'             => $this->mapType($item['format'] ?? '', $item['countryOfOrigin'] ?? null),
            'status'           => $this->mapStatus($item['status'] ?? ''),
            'volumes'          => $item['volumes'] ?? null,
            'score'            => isset($item['averageScore']) ? round($item['averageScore'] / 10, 2) : null,
            'synopsis'         => $this->cleanDescription($item['description'] ?? null),
            'published_from'   => $this->buildDate($item['startDate'] ?? null),
            'already_imported' => isset($imported[$item['id']]),
        ], $data);
    }
}
