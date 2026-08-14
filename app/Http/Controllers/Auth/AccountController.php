<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    /**
     * Switch ke akun lain yang sudah ke-link di session ini — TANPA re-auth, karena
     * kepemilikannya sudah dibuktikan sekali pas akun itu ditambahkan lewat "Tambah Akun".
     */
    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => ['required', 'uuid'],
        ]);

        $linked = session('linked_account_ids', []);

        // Wajib: target harus sudah pernah login beneran di sesi ini — jangan pernah percaya
        // user_id dari client tanpa validasi ini, atau siapa pun bisa "switch" ke akun sembarangan.
        abort_unless(in_array($request->user_id, $linked, true), 403);

        $target = User::find($request->user_id);
        abort_if(! $target || $target->is_banned, 403);

        Auth::login($target, remember: true);
        $request->session()->regenerate();
        // regenerate() TIDAK menghapus data session (default $destroy = false), jadi
        // linked_account_ids tetap ada setelah baris ini — sengaja tidak di-set ulang.

        $default = $target->isAdmin()
            ? route('admin.dashboard', absolute: false)
            : route('dashboard', absolute: false);

        return redirect()->intended($default);
    }

    /**
     * Logout dari akun yang lagi aktif SAJA (pola X/Twitter) — kalau masih ada akun lain yang
     * ke-link, otomatis pindah ke situ. Kalau ini akun terakhir, sama efeknya dengan logout total
     * (delegasi ke SsoController::logout() yang sudah handle SSO-down gracefully).
     */
    public function logoutCurrent(Request $request, SsoController $ssoController): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $currentId = auth()->id();
        $linked = collect(session('linked_account_ids', []))
            ->reject(fn ($id) => $id === $currentId)
            ->values();

        $nextId = $linked->first();

        if (! $nextId) {
            // Nggak ada akun lain yang ke-link — ini efektif logout total.
            return $ssoController->logout($request);
        }

        $next = User::find($nextId);

        if (! $next || $next->is_banned) {
            // Akun berikutnya di daftar sudah tidak valid — buang dari daftar, coba lagi.
            session(['linked_account_ids' => $linked->reject(fn ($id) => $id === $nextId)->values()->all()]);

            return $this->logoutCurrent($request, $ssoController);
        }

        Auth::login($next, remember: true);
        $request->session()->regenerate();
        session(['linked_account_ids' => $linked->all()]);

        $default = $next->isAdmin()
            ? route('admin.dashboard', absolute: false)
            : route('dashboard', absolute: false);

        return redirect()->intended($default);
    }
}
