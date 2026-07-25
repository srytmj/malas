<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\CollectionVolume;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Collection::class);

        $users = User::query()
            ->select('users.id', 'users.name', 'users.email', 'users.avatar')
            ->withCount('collections')
            ->addSelect(['owned_volumes_count' => CollectionVolume::selectRaw('count(*)')
                ->join('collections', 'collections.id', '=', 'collection_volumes.collection_id')
                ->whereColumn('collections.user_id', 'users.id'),
            ])
            ->whereHas('collections')
            ->orderByDesc('collections_count')
            ->paginate(20)
            ->through(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'avatar' => $u->avatar,
                'collections_count' => $u->collections_count,
                'owned_volumes_count' => (int) $u->owned_volumes_count,
            ]);

        return Inertia::render('Admin/Collections/Index', [
            'users' => $users,
        ]);
    }

    public function show(User $user): Response
    {
        $this->authorize('viewAny', Collection::class);

        $collections = $user->collections()
            ->with('series')
            ->withCount('collectionVolumes')
            ->latest()
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'series_title' => $c->series->title_romaji,
                'series_type' => $c->series->type,
                'owned_volumes_count' => $c->collection_volumes_count,
                'total_volumes' => $c->series->total_volumes,
                'condition' => $c->condition,
                'acquired_at' => $c->acquired_at?->toDateString(),
            ]);

        return Inertia::render('Admin/Collections/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
            ],
            'collections' => $collections,
        ]);
    }
}
