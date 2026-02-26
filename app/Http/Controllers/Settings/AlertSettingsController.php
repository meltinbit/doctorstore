<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AlertSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/alerts', [
            'alert_frequency' => $request->user()->alert_frequency,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'alert_frequency' => ['required', Rule::in(['none', 'daily', 'weekly'])],
        ]);

        $request->user()->update(['alert_frequency' => $request->alert_frequency]);

        return back()->with('status', 'alert-settings-updated');
    }
}
