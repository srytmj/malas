<?php

namespace App\Http\Controllers;

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
}
