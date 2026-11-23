<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit');
    }

    public function photo(Request $request): BinaryFileResponse
    {
        $path = $request->user()->profile_photo_path;

        abort_unless(
            is_string($path)
                && preg_match(
                    '/\Aprofile-photos\/[A-Za-z0-9_-]+\.(?:jpe?g|png|webp)\z/i',
                    $path,
                ) === 1
                && Storage::disk('local')->exists($path),
            404,
        );

        $response = response()->file(Storage::disk('local')->path($path), [
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'photo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:max_width=2048,max_height=2048',
            ],
        ]);

        $user = $request->user();
        $previousPath = $user->profile_photo_path;
        $newPath = $validated['photo']->store('profile-photos', 'local');

        if (! is_string($newPath)) {
            throw new RuntimeException('The profile photo could not be stored.');
        }

        try {
            $user->profile_photo_path = $newPath;
            $user->saveOrFail();
        } catch (Throwable $exception) {
            if (! Storage::disk('local')->delete($newPath)) {
                report(new RuntimeException(
                    'The new profile photo could not be removed after a failed profile update.',
                    previous: $exception,
                ));
            }

            throw $exception;
        }

        if ($previousPath && ! Storage::disk('local')->delete($previousPath)) {
            report(new RuntimeException('The previous profile photo could not be removed.'));
        }

        return redirect()->route('profile.edit')
            ->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function deletePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        $previousPath = $user->profile_photo_path;

        $user->profile_photo_path = null;
        $user->saveOrFail();

        if ($previousPath && ! Storage::disk('local')->delete($previousPath)) {
            report(new RuntimeException('The profile photo could not be removed from storage.'));
        }

        return redirect()->route('profile.edit')
            ->with('success', 'Foto profil berhasil dihapus.');
    }
}
