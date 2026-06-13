<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Collection\Models\LoanItem;
use App\Modules\Collection\Models\UserCollection;
use App\Modules\Collection\Models\UserLibrary;
use App\Modules\Collection\Models\Volume;
use App\Modules\Core\Models\Series;
use App\Modules\Jikan\JikanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AdminApiController extends Controller
{
    public function jikanSearch(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $local = Series::where('title_romaji', 'like', "%{$q}%")
            ->orWhere('title_english', 'like', "%{$q}%")
            ->limit(5)
            ->get();

        $toResult = fn(Series $s, bool $inLibrary) => [
            'mal_id'        => $s->mal_id,
            'title'         => $s->title_romaji,
            'title_english' => $s->title_english,
            'cover'         => $s->cover_image_path ? Storage::url($s->cover_image_path) : null,
            'status'        => $s->status,
            'volumes'       => $s->total_volumes,
            'in_library'    => $inLibrary,
        ];

        try {
            $res = Http::timeout(8)->get('https://api.jikan.moe/v4/manga', [
                'q' => $q, 'limit' => 15, 'type' => 'manga',
            ]);

            $localMalIds = $local->pluck('mal_id')->filter()->toArray();

            $jikan = $res->ok()
                ? collect($res->json('data', []))
                    ->filter(fn($m) => !in_array($m['mal_id'], $localMalIds))
                    ->map(fn($m) => [
                        'mal_id'        => $m['mal_id'],
                        'title'         => $m['title'],
                        'title_english' => $m['title_english'] ?? null,
                        'cover'         => $m['images']['jpg']['image_url'] ?? null,
                        'status'        => $m['status'] ?? null,
                        'volumes'       => $m['volumes'] ?? null,
                        'in_library'    => false,
                    ])
                    ->values()
                : collect();

            return response()->json(
                $local->map(fn($s) => $toResult($s, true))->merge($jikan)->values()
            );
        } catch (\Throwable) {
            return response()->json($local->map(fn($s) => $toResult($s, true))->values());
        }
    }

    public function jikanImport(Request $request): JsonResponse
    {
        $malId = (int) $request->input('mal_id');
        if (! $malId) {
            return response()->json(['error' => 'mal_id required'], 422);
        }

        $series = Series::where('mal_id', $malId)->first();

        if (! $series) {
            try {
                $res = Http::timeout(15)->get("https://api.jikan.moe/v4/manga/{$malId}");
                if ($res->failed()) {
                    return response()->json(['error' => 'Jikan API tidak tersedia'], 503);
                }
                $manga = $res->json('data');
                app(JikanService::class)->upsertPage([$manga]);
                $series = Series::where('mal_id', $malId)->first();
                if (! $series) {
                    return response()->json(['error' => 'Gagal menyimpan series'], 500);
                }
            } catch (\Throwable $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }

        $totalVolumes    = (int) ($series->total_volumes ?? 0);
        $existingNumbers = $series->volumes()->pluck('volume_number')->map(fn($n) => (int) $n)->toArray();

        if ($totalVolumes > 0) {
            for ($i = 1; $i <= $totalVolumes; $i++) {
                if (! in_array($i, $existingNumbers)) {
                    Volume::create(['series_id' => $series->id, 'volume_number' => $i]);
                }
            }
        }

        $volumes = $series->volumes()->orderBy('volume_number')->get(['id', 'volume_number', 'title']);

        return response()->json([
            'series' => [
                'id'            => $series->id,
                'mal_id'        => $series->mal_id,
                'title_romaji'  => $series->title_romaji,
                'title_english' => $series->title_english,
                'cover_url'     => $series->cover_image_path ? Storage::url($series->cover_image_path) : null,
                'status'        => $series->status,
                'total_volumes' => $series->total_volumes,
            ],
            'volumes' => $volumes,
        ]);
    }

    public function searchSeries(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $series = Series::where('title_romaji', 'like', "%{$q}%")
            ->orWhere('title_english', 'like', "%{$q}%")
            ->select(['id', 'title_romaji', 'title_english', 'total_volumes'])
            ->orderBy('title_romaji')
            ->limit(25)
            ->get();

        return response()->json($series);
    }

    public function seriesVolumes(Request $request, Series $series): JsonResponse
    {
        $ownedVolumeIds = [];

        if ($request->user_id) {
            $library = UserLibrary::where('user_id', $request->user_id)
                ->where('series_id', $series->id)
                ->first();
            if ($library) {
                $ownedVolumeIds = $library->collections()->pluck('volume_id')->toArray();
            }
        }

        $volumes = $series->volumes()
            ->orderBy('volume_number')
            ->when($ownedVolumeIds, fn($q) => $q->whereNotIn('id', $ownedVolumeIds))
            ->get(['id', 'volume_number', 'title']);

        return response()->json([
            'series'  => [
                'id'           => $series->id,
                'title_romaji' => $series->title_romaji,
            ],
            'volumes' => $volumes,
        ]);
    }

    public function seriesVolumeStatus(Request $request, Series $series): JsonResponse
    {
        $userId  = $request->input('user_id');
        $volumes = $series->volumes()->orderBy('volume_number')->get();

        if (! $userId) {
            return response()->json([
                'volumes' => $volumes->map(fn($v) => [
                    'id' => $v->id, 'volume_number' => $v->volume_number,
                    'title' => $v->title, 'isbn' => $v->isbn,
                    'status' => 'not_owned', 'collection' => null,
                ]),
            ]);
        }

        $library = UserLibrary::where('user_id', $userId)->where('series_id', $series->id)->first();

        $collections = $library
            ? UserCollection::with('volume')->where('user_library_id', $library->id)
                ->whereNull('deleted_at')->get()->keyBy('volume_id')
            : collect();

        $loanedCollectionIds = [];
        if ($collections->isNotEmpty()) {
            $loanedCollectionIds = LoanItem::whereIn('user_collection_id', $collections->pluck('id'))
                ->whereNull('returned_at')
                ->pluck('user_collection_id')
                ->toArray();
        }

        return response()->json([
            'volumes' => $volumes->map(function ($v) use ($collections, $loanedCollectionIds) {
                $col = $collections->get($v->id);
                if (! $col) {
                    return ['id' => $v->id, 'volume_number' => $v->volume_number, 'title' => $v->title, 'isbn' => $v->isbn, 'status' => 'not_owned', 'collection' => null];
                }
                return [
                    'id'            => $v->id,
                    'volume_number' => $v->volume_number,
                    'title'         => $v->title,
                    'isbn'          => $v->isbn,
                    'status'        => in_array($col->id, $loanedCollectionIds) ? 'loaned' : 'owned',
                    'collection'    => [
                        'id'             => $col->id,
                        'condition'      => $col->condition,
                        'is_for_loan'    => $col->is_for_loan,
                        'purchase_price' => $col->purchase_price,
                        'purchase_date'  => $col->purchase_date?->format('Y-m-d'),
                        'notes'          => $col->notes,
                        'edit_url'       => route('admin.collections.edit', $col->id),
                    ],
                ];
            }),
        ]);
    }

    public function seriesUserCollections(Request $request, Series $series): JsonResponse
    {
        $userId = $request->input('user_id');
        if (! $userId) {
            return response()->json(['collections' => []]);
        }

        $library = UserLibrary::where('user_id', $userId)
            ->where('series_id', $series->id)
            ->first();

        if (! $library) {
            return response()->json(['collections' => []]);
        }

        $collections = UserCollection::with('volume')
            ->where('user_library_id', $library->id)
            ->whereNull('deleted_at')
            ->get()
            ->sortBy(fn($c) => $c->volume?->volume_number)
            ->values()
            ->map(fn($c) => [
                'id'             => $c->id,
                'volume_id'      => $c->volume_id,
                'volume_number'  => $c->volume?->volume_number,
                'volume_title'   => $c->volume?->title,
                'condition'      => $c->condition,
                'is_for_loan'    => $c->is_for_loan,
                'purchase_price' => $c->purchase_price,
                'purchase_date'  => $c->purchase_date,
                'notes'          => $c->notes,
                'edit_url'       => route('admin.collections.edit', $c->id),
            ]);

        return response()->json(['collections' => $collections]);
    }
}
