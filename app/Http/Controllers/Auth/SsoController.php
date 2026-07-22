<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SsoController extends Controller
{
    public function redirect(): RedirectResponse
    {
        $codeVerifier = bin2hex(random_bytes(32));
        $codeChallenge = rtrim(
            strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'),
            '='
        );
        $state = bin2hex(random_bytes(16));

        session([
            'sso_code_verifier' => $codeVerifier,
            'sso_state'         => $state,
        ]);

        $query = http_build_query([
            'response_type'         => 'code',
            'client_id'             => config('sso.client_id'),
            'redirect_uri'          => config('sso.redirect_uri'),
            'scope'                 => 'profile:read',
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
            'state'                 => $state,
        ]);

        return redirect(config('sso.base_url').'/oauth/authorize?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_if(! $request->filled('code'), 400, 'Missing authorization code');
        abort_if($request->state !== session('sso_state'), 403, 'Invalid state');

        $tokenResult = $this->curlRequest('POST', config('sso.base_url').'/oauth/token', [
            'grant_type'    => 'authorization_code',
            'code'          => $request->code,
            'redirect_uri'  => config('sso.redirect_uri'),
            'client_id'     => config('sso.client_id'),
            'client_secret' => config('sso.client_secret'),
            'code_verifier' => session('sso_code_verifier'),
        ]);

        abort_if($tokenResult === null, 502, 'Tidak dapat menghubungi server SSO.');
        abort_if($tokenResult['status'] >= 400, 502, 'SSO token exchange failed');

        $tokenResponse = json_decode($tokenResult['body'], true);

        abort_if(empty($tokenResponse['access_token']), 502, 'SSO token exchange returned no access token');

        $profileResult = $this->curlRequest('GET', config('sso.base_url').'/api/user', [], [
            'Authorization: Bearer '.$tokenResponse['access_token'],
        ]);

        abort_if($profileResult === null, 502, 'Tidak dapat menghubungi server SSO.');
        abort_if($profileResult['status'] >= 400, 502, 'Failed to fetch profile from SSO');

        $profile = json_decode($profileResult['body'], true);

        /** @var User $user */
        $user = User::query()->firstOrNew(['sso_id' => $profile['id']]);
        $isNew = ! $user->exists;

        $user->fill([
            'name'     => $profile['name'],
            'username' => $profile['username'],
            'email'    => $profile['email'],
            'avatar'   => $profile['avatar'],
        ]);

        if ($isNew) {
            $user->role = ($profile['role']['slug'] ?? null) === 'superadmin' ? 'super_admin' : 'user';
        }

        $user->save();

        session()->forget(['sso_code_verifier', 'sso_state']);
        session([
            'sso_access_token'  => $tokenResponse['access_token'],
            'sso_refresh_token' => $tokenResponse['refresh_token'],
        ]);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        if ($user->is_banned) {
            return redirect()->route('banned');
        }

        $default = $user->isAdmin()
            ? route('admin.dashboard', absolute: false)
            : route('dashboard', absolute: false);

        return redirect()->intended($default);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(config('sso.base_url').'/logout');
    }

    /**
     * Plain curl_exec request. The Guzzle-based Http facade intermittently
     * hangs on this environment's PHP-FPM (curl_multi never completes),
     * while a blocking curl_exec has been reliable — see SsoController
     * connectivity investigation.
     *
     * @return array{status: int, body: string}|null null on transport failure
     */
    private function curlRequest(string $method, string $url, array $formFields = [], array $headers = []): ?array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($formFields));
        }

        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $body === false) {
            \Illuminate\Support\Facades\Log::error("SSO request to {$url} failed: [{$errno}] {$error}");
            return null;
        }

        return ['status' => $status, 'body' => $body];
    }
}
