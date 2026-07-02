<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Collection::class);

        $collections = Collection::with(['user', 'series'])
            ->withCount('collectionVolumes')
            ->latest()
            ->paginate(20)
            ->through(fn ($c) => [
                'id'                       => $c->id,
                'user_name'                => $c->user->name,
                'user_email'               => $c->user->email,
                'series_title'             => $c->series->title_romaji,
                'series_type'              => $c->series->type,
                'owned_volumes_count' => $c->collection_volumes_count,
                'total_volumes'            => $c->series->total_volumes,
                'acquired_at'              => $c->acquired_at?->toDateString(),
            ]);

        return Inertia::render('Admin/Collections/Index', [
            'collections' => $collections,
        ]);
    }
}
