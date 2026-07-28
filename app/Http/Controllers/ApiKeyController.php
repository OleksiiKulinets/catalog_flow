<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ApiKeyController extends Controller
{
    /**
     * Store (or replace) the authenticated user's OpenAI API key.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'openai_api_key' => ['required', 'string', 'min:20', 'max:200'],
        ]);

        $key = trim($validated['openai_api_key']);

        $request->user()->apiKey()->updateOrCreate([], [
            'provider' => 'openai',
            'encrypted_key' => $key,
            'last_four' => substr($key, -4),
        ]);

        return Redirect::route('profile.edit')->with('status', 'api-key-updated');
    }

    /**
     * Delete the authenticated user's OpenAI API key.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->apiKey()->delete();

        return Redirect::route('profile.edit')->with('status', 'api-key-deleted');
    }
}
