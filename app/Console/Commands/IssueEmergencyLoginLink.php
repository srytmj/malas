<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\SsoFallbackToken;
use App\Models\User;
use Illuminate\Console\Command;

class IssueEmergencyLoginLink extends Command
{
    /**
     * Identifier boleh role keyword (super_admin/admin/user, dengan atau tanpa underscore)
     * atau email/username spesifik. Default super_admin karena ini yang paling sering
     * dibutuhkan pas SSO down — akun tertinggi yang harus tetap bisa masuk.
     */
    protected $signature = 'sso:emergency-login {identifier=super_admin : Role (super_admin/admin/user) atau email/username spesifik}';

    protected $description = 'Terbitkan link login sekali-pakai tanpa lewat SSO — dipakai kalau whitearchive.id benar-benar tidak bisa diakses. Butuh akses CLI/SSH ke server.';

    private const ROLE_ALIASES = [
        'super_admin' => 'super_admin',
        'superadmin' => 'super_admin',
        'admin' => 'admin',
        'user' => 'user',
    ];

    public function handle(): int
    {
        $identifier = strtolower(trim((string) $this->argument('identifier')));

        $user = self::ROLE_ALIASES[$identifier] ?? null
            ? $this->resolveByRole(self::ROLE_ALIASES[$identifier])
            : $this->resolveByIdentifier($identifier);

        if (! $user) {
            $this->error("Tidak ada user aktif (belum di-ban) yang cocok dengan \"{$identifier}\".");

            return self::FAILURE;
        }

        $this->info('Akan menerbitkan link login darurat untuk:');
        $this->line("  Nama     : {$user->name}");
        $this->line("  Email    : {$user->email}");
        $this->line('  Role     : '.$user->role);

        if (! $this->confirm('Lanjutkan?', true)) {
            $this->comment('Dibatalkan.');

            return self::SUCCESS;
        }

        $token = SsoFallbackToken::issueFor($user);
        $url = route('sso.fallback.consume', ['token' => $token]);

        ActivityLog::record(
            'auth.emergency_login_issued',
            "Link login darurat (CLI, tanpa SSO) diterbitkan untuk {$user->name}.",
            $user,
        );

        $this->newLine();
        $this->info('Link login (berlaku 15 menit, sekali pakai):');
        $this->line($url);
        $this->newLine();
        $this->comment('Buka link ini di browser untuk langsung login sebagai user di atas.');

        return self::SUCCESS;
    }

    private function resolveByRole(string $role): ?User
    {
        $candidates = User::where('role', $role)
            ->where('is_banned', false)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        if ($candidates->isEmpty()) {
            return null;
        }

        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        $choice = $this->choice(
            "Ada {$candidates->count()} user dengan role {$role} — pilih salah satu:",
            $candidates->map(fn ($u) => "{$u->name} ({$u->email})")->all(),
        );

        $index = array_search($choice, $candidates->map(fn ($u) => "{$u->name} ({$u->email})")->all(), true);

        return $candidates->get($index);
    }

    private function resolveByIdentifier(string $identifier): ?User
    {
        return User::where('is_banned', false)
            ->where(fn ($q) => $q->where('email', $identifier)->orWhere('username', $identifier))
            ->first();
    }
}
