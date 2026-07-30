<?php

namespace CatFlow\Admin\Http\Controllers\Settings;

use CatFlow\Admin\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class LocaleController extends Controller
{
    /**
     * Update the authenticated user's interface language.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:en,uk'],
        ]);

        $request->user()->update(['locale' => $validated['locale']]);

        return Redirect::route('profile.edit')->with('status', 'locale-updated');
    }
}
