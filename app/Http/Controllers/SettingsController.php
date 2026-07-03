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
        $user = auth()->user();

        return Inertia::render('Settings/Index', [
            'name_can_change_at' => $user->name_changed_at
                ? $user->name_changed_at->addHours(2)->toIso8601String()
                : null,
        ]);
    }

    public function updateName(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if ($user->name_changed_at && $user->name_changed_at->addHours(2)->isFuture()) {
            $canAt = $user->name_changed_at->addHours(2)->format('H:i, d M Y');
            return redirect()->back()->with('error', "Kamu bisa ganti nama lagi pada {$canAt}.");
        }

        $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $user->update([
            'name'            => $request->name,
            'name_changed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Nama berhasil diperbarui.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        auth()->user()->update(['password' => $request->password]);

        return redirect()->back()->with('success', 'Password berhasil diperbarui.');
    }
}
