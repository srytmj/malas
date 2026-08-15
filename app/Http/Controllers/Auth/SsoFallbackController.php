<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SsoFallbackLoginMail;
use App\Models\ActivityLog;
use App\Models\SsoFallbackToken;
use App\Models\User;
use App\Services\AccountLinkService;
use App\Services\MailSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SsoFallbackController extends Controller
{
    public function __construct(private MailSettingsService $mail, private AccountLinkService $accountLink) {}

    public function show(): Response
    {
        return Inertia::render('Auth/SsoFallback');
    }

    /**
     * Selalu balas pesan generik yang sama baik email-nya terdaftar atau tidak — supaya endpoint
     * ini tidak bisa dipakai buat enumerasi email user yang valid.
     */
    public function send(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->where('is_banned', false)->first();

        if ($user && $this->mail->isConfigured()) {
            $token = SsoFallbackToken::issueFor($user);
            $loginUrl = route('sso.fallback.consume', ['token' => $token]);

            // Kegagalan provider mail (key salah, Resend down, dll) tidak boleh bikin request ini
            // 500 — tetap balas pesan generik yang sama, cuma dicatat buat admin.
            try {
                $this->mail->send($user->email, new SsoFallbackLoginMail($user, $loginUrl));
                ActivityLog::record('auth.fallback_requested', "Link login tanpa SSO diminta untuk {$user->name}.", $user);
            } catch (\Throwable $e) {
                report($e);
                Log::error("Gagal kirim email fallback login untuk {$user->email}: {$e->getMessage()}");
                ActivityLog::record('auth.fallback_mail_error', "Gagal kirim link login tanpa SSO untuk {$user->name}: {$e->getMessage()}", $user);
            }
        }

        return redirect()->back()->with('success', __('flash.sso_fallback.link_sent'));
    }

    public function consume(string $token): RedirectResponse
    {
        $record = SsoFallbackToken::findValid($token);

        abort_if(! $record, 400, 'Link ini sudah tidak berlaku — sudah dipakai, kedaluwarsa, atau salah. Minta link baru.');

        $user = $record->user;
        abort_if(! $user || $user->is_banned, 403);

        $record->markUsed();

        $this->accountLink->loginAs($user);

        ActivityLog::record('auth.fallback_login', "{$user->name} login tanpa SSO (fallback).", $user);

        $default = $user->isAdmin()
            ? route('admin.dashboard', absolute: false)
            : route('dashboard', absolute: false);

        return redirect()->intended($default);
    }
}
