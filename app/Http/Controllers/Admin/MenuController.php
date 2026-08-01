<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Menu::class);

        return $this->renderSidebar('Admin/Menus/Index', fn (Builder $q) => $q
            ->whereJsonContains('role_access', 'admin')
            ->orWhereJsonContains('role_access', 'super_admin'));
    }

    public function userSidebar(Request $request): Response
    {
        $this->authorize('viewAny', Menu::class);

        return $this->renderSidebar('Admin/Menus/UserSidebar', fn (Builder $q) => $q
            ->whereJsonContains('role_access', 'user'));
    }

    private function renderSidebar(string $component, Closure $scope): Response
    {
        $menus = Menu::where($scope)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'key' => $m->key,
                'label' => $m->label,
                'icon' => $m->icon,
                'route_name' => $m->route_name,
                'parent_key' => $m->parent_key,
                'sort_order' => $m->sort_order,
                'is_visible' => $m->is_visible,
                'is_maintenance' => $m->is_maintenance,
                'maintenance_message' => $m->maintenance_message,
                'role_access' => $m->role_access,
                'is_shared' => count($m->role_access ?? []) > 1,
            ]);

        $topLevel = $menus->filter(fn ($m) => ! $m['parent_key'])->values();
        $groups = $menus
            ->filter(fn ($m) => $m['parent_key'])
            ->groupBy('parent_key')
            ->map(fn ($items, $parentKey) => [
                'parent_key' => $parentKey,
                'label' => Menu::where('key', $parentKey)->value('label') ?? $parentKey,
                'items' => $items->values(),
            ])
            ->values();

        return Inertia::render($component, [
            'topLevel' => $topLevel,
            'groups' => $groups,
        ]);
    }

    public function reorder(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Menu::class);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'uuid', 'exists:menus,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['items'] as $item) {
                Menu::whereKey($item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });

        return redirect()->back()->with('success', 'Urutan menu berhasil diperbarui.');
    }

    public function edit(Request $request, Menu $menu): Response
    {
        $this->authorize('view', $menu);

        return Inertia::render('Admin/Menus/Edit', [
            'menu' => [
                'id' => $menu->id,
                'key' => $menu->key,
                'label' => $menu->label,
                'icon' => $menu->icon,
                'route_name' => $menu->route_name,
                'sort_order' => $menu->sort_order,
                'is_visible' => $menu->is_visible,
                'is_maintenance' => $menu->is_maintenance,
                'maintenance_message' => $menu->maintenance_message,
                'role_access' => $menu->role_access,
            ],
        ]);
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $this->authorize('update', $menu);

        $validated = $request->validate([
            'label' => ['sometimes', 'required', 'string', 'max:100'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:50'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_visible' => ['sometimes', 'boolean'],
            'is_maintenance' => ['sometimes', 'boolean'],
            'maintenance_message' => ['sometimes', 'nullable', 'string', 'max:500'],
            'role_access' => ['sometimes', 'array'],
            'role_access.*' => ['in:super_admin,admin,user'],
        ]);

        $menu->update($validated);

        if ($request->wantsJson() || $request->header('X-Inertia')) {
            return redirect()->back()->with('success', 'Menu berhasil diperbarui.');
        }

        return redirect()->route('admin.menus.index')
            ->with('success', 'Menu berhasil diperbarui.');
    }
}
