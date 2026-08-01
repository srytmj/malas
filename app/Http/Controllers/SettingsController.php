<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Index', [
            'sso_account_url' => config('sso.base_url').'/account',
        ]);
    }

    public function updateProfileVisibility(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'is_profile_public' => ['required', 'boolean'],
        ]);

        $request->user()->update($data);

        $state = $data['is_profile_public'] ? 'publik' : 'privat';

        return redirect()->back()->with('success', "Profil kamu sekarang {$state}.");
    }

    public function updateLocale(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'in:id,en,ja'],
        ]);

        $request->user()->update($data);

        return redirect()->back();
    }
}
