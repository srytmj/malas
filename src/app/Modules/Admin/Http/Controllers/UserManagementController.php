<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Core\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatableResponse($request);
        }
        return view('admin.users.index');
    }

    private function datatableResponse(Request $request): JsonResponse
    {
        $query = User::withTrashed();
        $total = User::withTrashed()->count();

        if ($search = $request->input('search.value')) {
            $query->where(fn($q) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
            );
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }
        if ($status = $request->input('status')) {
            if ($status === 'banned') $query->where('is_banned', true);
            if ($status === 'deleted') $query->whereNotNull('deleted_at');
        }

        $filtered = $query->count();

        $colMap = [0 => 'name', 1 => 'email', 2 => 'role', 3 => 'created_at'];
        $colIdx = (int) $request->input('order.0.column', 0);
        $dir    = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($colMap[$colIdx] ?? 'name', $dir);

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        $data   = $query->offset($start)->limit($length)->get();

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data->map(fn($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'role'       => $u->role,
                'is_banned'  => $u->is_banned,
                'trashed'    => $u->trashed(),
                'show_url'   => route('admin.users.show', $u->id),
                'ban_url'    => $u->trashed() || $u->isSuperAdmin() ? null : route('admin.users.ban', $u->id),
                'unban_url'  => $u->is_banned ? route('admin.users.unban', $u->id) : null,
                'delete_url' => $u->trashed() || $u->isSuperAdmin() ? null : route('admin.users.destroy', $u->id),
            ]),
        ]);
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:user,super_admin',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dibuat.');
    }

    public function show(User $user)
    {
        $user->load(['library.series', 'loans' => fn($q) => $q->latest()->limit(10)]);
        return view('admin.users.show', compact('user'));
    }

    public function ban(Request $request, User $user)
    {
        $request->validate(['reason' => 'required|string|min:5']);

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Super admin tidak bisa diblokir.');
        }

        $user->ban($request->reason, auth()->id());
        return back()->with('success', "User {$user->name} berhasil diblokir.");
    }

    public function unban(User $user)
    {
        $user->unban(auth()->id());
        return back()->with('success', "User {$user->name} berhasil di-unban.");
    }

    public function destroy(Request $request, User $user)
    {
        $request->validate(['reason' => 'required|string|min:5']);

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Super admin tidak bisa dihapus.');
        }

        $user->deleteWithReason($request->reason);
        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }
}
