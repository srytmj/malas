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
     * Login sebagai $user. Kalau lagi ada user LAIN yang aktif login di sesi ini, user itu
     * otomatis ditambahkan ke daftar akun ke-link (bukan diganti/hilang) — supaya bisa switch
     * balik tanpa re-auth. Keputusan ini SENGAJA berdasarkan status login saat ini (`auth()->check()`),
     * bukan flag eksplisit dari UI ("Tambah Akun" vs login biasa) — karena magic link yang
     * diterbitkan lewat CLI (`sso:emergency-login`) atau yang di-klik dari email nggak pernah lewat
     * modal "Tambah Akun", jadi flag manapun yang dikirim dari situ nggak akan pernah ke-set. Kalau
     * user memang mau logout dulu baru login akun lain, itu ditangani lewat AccountController's
     * logoutCurrent()/logout total — bukan tanggung jawab method ini.
     */
    public function loginAs(User $user): void
    {
        $linked = collect(session('linked_account_ids', []));

        if (auth()->check() && auth()->id() !== $user->id) {
            $linked->push(auth()->id());
        }

        $linked->push($user->id);

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        session(['linked_account_ids' => $linked->unique()->values()->all()]);
    }
}
