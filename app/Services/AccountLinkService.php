<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Multi-account switching — session-based, TIDAK ada link permanen di DB. Sengaja begitu:
 * switch akun selalu butuh bukti kontrol beneran (login ulang) ke akun target, jadi nyimpen
 * link permanen nggak ngurangin langkah keamanan apa pun, cuma nambah kompleksitas. Daftar akun
 * yang "ke-link" cuma hidup di session (`linked_account_ids`) — ganti browser/device, ilang,
 * harus link ulang dari nol.
 */
class AccountLinkService
{
    /**
     * Login sebagai $user. Kalau $linkMode true dan lagi ada user yang login (proses "Tambah
     * Akun"), user yang lama ditambahkan ke daftar akun ke-link juga — supaya bisa switch balik
     * tanpa re-auth selama sesi browser ini masih hidup.
     */
    public function loginAs(User $user, bool $linkMode): void
    {
        $linked = collect(session('linked_account_ids', []));

        if ($linkMode && auth()->check()) {
            $linked->push(auth()->id());
        }

        $linked->push($user->id);

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        session(['linked_account_ids' => $linked->unique()->values()->all()]);
        session()->forget('sso_link_mode');
    }
}
