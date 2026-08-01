<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Services\RanobeDbService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RanobeDbController extends Controller
{
    public function __construct(private RanobeDbService $ranobedb) {}

    public function searchJson(Request $request): JsonResponse
    {
        $this->authorize('create', Series::class);

        $q = trim((string) $request->get('q', ''));

        if (! $q) {
            return response()->json(['results' => [], 'error' => null]);
        }

        try {
            $raw = $this->ranobedb->searchSeries($q, 1);

            return response()->json([
                'results' => $this->formatResults($raw['data']),
                'error' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['results' => [], 'error' => $e->getMessage()]);
        }
    }

    public function detailJson(Request $request): JsonResponse
    {
        $this->authorize('create', Series::class);

        $request->validate([
            'ranobedb_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $data = $this->ranobedb->getSeries((int) $request->ranobedb_id);
        } catch (\Exception $e) {
            return response()->json(['data' => null, 'error' => $e->getMessage()]);
        }

        $attributes = $this->mapToSeries($data);
        $mainBook = $this->mainCoverBook($data['books'] ?? []);
        $attributes['cover_url'] = ($mainBook && ! empty($mainBook['image']['filename']))
            ? "https://images.ranobedb.org/{$mainBook['image']['filename']}"
            : null;

        return response()->json(['data' => $attributes, 'error' => null]);
    }

    public function index(Request $request): Response
    {
        $this->authorize('create', Series::class);

        $results = [];
        $pagination = [];
        $error = null;

        if ($q = $request->get('q')) {
            try {
                $raw = $this->ranobedb->searchSeries(trim($q), (int) $request->get('page', 1));
                $results = $this->formatResults($raw['data']);
                $pagination = $raw['pagination'];
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        return Inertia::render('Admin/RanobeDb/Index', [
            'results' => $results,
            'pagination' => $pagination,
            'filters' => ['q' => $request->get('q', '')],
            'error' => $error,
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Series::class);

        $request->validate([
            'ranobedb_id' => ['required', 'integer', 'min:1'],
        ]);

        $ranobedbId = (int) $request->ranobedb_id;

        try {
            $data = $this->ranobedb->getSeries($ranobedbId);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $attributes = $this->mapToSeries($data);
        $mainBook = $this->mainCoverBook($data['books'] ?? []);

        $existing = Series::where('ranobedb_id', $ranobedbId)->first();

        if ($existing) {
            if ($existing->cover_path) {
                unset($attributes['cover_path']);
            }
            $existing->update($attributes);
            $series = $existing;
            $message = 'Series berhasil diperbarui dari RanobeDB.';
        } else {
            if ($mainBook && ! empty($mainBook['image']['filename'])) {
                $attributes['cover_path'] = $this->ranobedb->downloadCover(
                    $mainBook['image']['filename'],
                    'ranobedb_'.$ranobedbId
                );
            }
            $series = Series::create($attributes);
            $message = 'Series berhasil diimpor dari RanobeDB.';
        }

        $generated = $this->generateVolumesFromBooks($series, $data['books'] ?? []);
        if ($generated > 0) {
            $message .= " {$generated} volume dibuat otomatis.";
        }

        return redirect()->back()->with('success', $message);
    }

    private function mapToSeries(array $data): array
    {
        $tags = $this->splitTagsByType($data['tags'] ?? []);
        $staff = $this->splitStaffByRole($data['staff'] ?? []);

        return [
            'ranobedb_id' => $data['id'],
            'title_romaji' => $this->pickRomajiTitle($data),
            'title_english' => $this->pickTitleByLang($data, 'en'),
            'title_japanese' => $this->pickTitleByLang($data, 'ja'),
            'synopsis' => $this->cleanDescription($data['description'] ?? null),
            'status' => $this->mapStatus($data['publication_status'] ?? 'unknown'),
            'type' => 'novel',
            'published_from' => $this->mapDate($data['start_date'] ?? null),
            'published_to' => $this->mapDate($data['end_date'] ?? null),
            'total_volumes' => count($data['books'] ?? []) ?: null,
            'score' => isset($data['rating']['score']) ? round($data['rating']['score'], 2) : null,
            'genres' => $tags['genres'],
            'themes' => $tags['themes'],
            'demographics' => $tags['demographics'],
            'authors' => $staff['authors'],
            'illustrators' => $staff['illustrators'],
            // RanobeDB has no adult-content flag — left false, admin toggles manually if needed.
            'is_adult' => false,
        ];
    }

    private function pickRomajiTitle(array $data): string
    {
        foreach (['romaji_orig', 'romaji', 'title_orig', 'title'] as $key) {
            if (! empty($data[$key])) {
                return $data[$key];
            }
        }

        return '';
    }

    private function pickTitleByLang(array $data, string $lang): ?string
    {
        if (($data['olang'] ?? null) === $lang) {
            return $data['title_orig'] ?: $data['title'] ?: null;
        }

        if (($data['lang'] ?? null) === $lang) {
            return $data['title'] ?: null;
        }

        foreach ($data['titles'] ?? [] as $title) {
            if (($title['lang'] ?? null) === $lang) {
                return $title['title'] ?? null;
            }
        }

        return null;
    }

    /** @return array{genres: array, demographics: array, themes: array} */
    private function splitTagsByType(array $tags): array
    {
        $genres = [];
        $demographics = [];
        $themes = [];

        foreach ($tags as $tag) {
            $name = $tag['name'] ?? null;
            if (! $name) {
                continue;
            }

            match ($tag['ttype'] ?? null) {
                'genre' => $genres[] = $name,
                'demographic' => $demographics[] = $name,
                default => $themes[] = $name,
            };
        }

        return [
            'genres' => array_values(array_unique($genres)),
            'demographics' => array_values(array_unique($demographics)),
            'themes' => array_values(array_unique($themes)),
        ];
    }

    /** @return array{authors: array, illustrators: array} */
    private function splitStaffByRole(array $staff): array
    {
        $authors = [];
        $illustrators = [];

        foreach ($staff as $member) {
            $name = $member['romaji'] ?? null;
            if (! $name) {
                $name = $member['name'] ?? null;
            }
            if (! $name) {
                continue;
            }

            match ($member['role_type'] ?? null) {
                'author' => $authors[] = $name,
                'artist' => $illustrators[] = $name,
                default => null,
            };
        }

        return [
            'authors' => array_values(array_unique($authors)),
            'illustrators' => array_values(array_unique($illustrators)),
        ];
    }

    private function mapDate(?int $packed): ?string
    {
        if (! $packed || $packed >= 99999999) {
            return null;
        }

        $str = str_pad((string) $packed, 8, '0', STR_PAD_LEFT);
        $year = substr($str, 0, 4);
        $month = substr($str, 4, 2) === '00' ? '01' : substr($str, 4, 2);
        $day = substr($str, 6, 2) === '00' ? '01' : substr($str, 6, 2);

        return "{$year}-{$month}-{$day}";
    }

    private function mapStatus(string $status): string
    {
        return match ($status) {
            'ongoing' => 'publishing',
            'completed' => 'finished',
            'hiatus' => 'on_hiatus',
            'stalled', 'cancelled' => 'discontinued',
            default => 'not_yet_published',
        };
    }

    private function cleanDescription(?string $description): ?string
    {
        if (! $description) {
            return null;
        }

        $text = strip_tags(str_ireplace(['<br>', '<br/>', '<br />'], "\n", $description));

        return trim($text) ?: null;
    }

    private function mainCoverBook(array $books): ?array
    {
        foreach ($books as $book) {
            if (($book['book_type'] ?? null) === 'main') {
                return $book;
            }
        }

        return $books[0] ?? null;
    }

    /**
     * Creates Volume rows from the series' main-type books, using the release date
     * already present in the series-detail response. ISBN/page-count import would
     * need a per-book detail call for each volume (N+1 across a whole series) —
     * intentionally not done here to keep import to a single API round trip.
     */
    private function generateVolumesFromBooks(Series $series, array $books): int
    {
        $existing = $series->volumes()->pluck('volume_number')->all();
        $created = 0;
        $number = 0;

        foreach ($books as $book) {
            if (($book['book_type'] ?? null) !== 'main') {
                continue;
            }

            $number++;

            if (in_array($number, $existing, true)) {
                continue;
            }

            $series->volumes()->create([
                'volume_number' => $number,
                'type' => 'regular',
                'published_at' => $this->mapDate($book['c_release_date'] ?? null),
            ]);
            $created++;
        }

        return $created;
    }

    /** @param array<int, array<string, mixed>> $data */
    private function formatResults(array $data): array
    {
        $ranobedbIds = array_column($data, 'id');
        $imported = Series::whereIn('ranobedb_id', $ranobedbIds)->pluck('id', 'ranobedb_id')->toArray();

        return array_map(fn ($item) => [
            'ranobedb_id' => $item['id'],
            'title' => $this->pickRomajiTitle($item),
            'title_display' => $item['title'] ?? null,
            'cover_url' => isset($item['book']['image']['filename'])
                ? "https://images.ranobedb.org/{$item['book']['image']['filename']}"
                : null,
            'volumes' => $item['c_num_books'] ?? null,
            'published_from' => $this->mapDate($item['c_start_date'] ?? null),
            'published_to' => $this->mapDate($item['c_end_date'] ?? null),
            'already_imported' => isset($imported[$item['id']]),
            'series_id' => $imported[$item['id']] ?? null,
        ], $data);
    }
}
