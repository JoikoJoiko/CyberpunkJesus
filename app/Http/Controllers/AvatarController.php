<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AvatarController extends Controller
{
    public function show(Request $request): StreamedResponse
    {
        $path = $request->user()->avatarPath();
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
        ]);

        $saved = Storage::disk('local')->putFileAs(
            'avatars', $request->file('avatar'), basename($request->user()->avatarPath())
        );

        return $saved
            ? to_route('profile.edit')->with('status', 'Аватар обновлён.')
            : back()->withErrors(['avatar' => 'Не удалось сохранить аватар.']);
    }
}
