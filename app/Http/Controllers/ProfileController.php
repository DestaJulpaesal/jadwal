<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Support\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $avatar = $request->file('avatar');
        $removeAvatar = (bool) ($validated['remove_avatar'] ?? false);
        unset($validated['avatar'], $validated['remove_avatar']);

        $oldPhotoPath = $user->profile_photo_path;
        $newPhotoPath = null;
        if ($avatar) {
            $newPhotoPath = $avatar->store('profile-photos');
            if (! is_string($newPhotoPath)) {
                throw new RuntimeException('Foto profil gagal disimpan. Silakan coba lagi.');
            }
            $user->profile_photo_path = $newPhotoPath;
        } elseif ($removeAvatar) {
            $user->profile_photo_path = null;
        }

        $user->fill($validated);

        if (config('auth.email_verification') && $user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        try {
            $user->save();
        } catch (\Throwable $exception) {
            if ($newPhotoPath) {
                Storage::disk('local')->delete($newPhotoPath);
            }

            throw $exception;
        }

        if ($oldPhotoPath && $oldPhotoPath !== $newPhotoPath && ($newPhotoPath || $removeAvatar)) {
            $this->deletePhoto($oldPhotoPath);
        }

        return Feedback::success(Redirect::route('profile.edit'), __('ui.account.profile_updated'));
    }

    public function photo(Request $request): BinaryFileResponse
    {
        $path = $request->user()->profile_photo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'confirmation' => ['required', 'in:HAPUS'],
            'password' => ['required', 'current_password'],
        ], __('validation.catalog.profile_deletion'));

        $user = $request->user();
        if ($user->isAdmin() && User::where('role', 'admin')->where('is_active', true)->count() <= 1) {
            return Redirect::back()->withErrors([
                'account' => __('ui.feedback.last_active_admin_deletion'),
            ], 'userDeletion');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function deletePhoto(string $path): void
    {
        $disk = Storage::disk('local');
        if ($disk->exists($path) && ! $disk->delete($path)) {
            Log::warning('A profile photo could not be deleted from local storage.', [
                'user_id' => Auth::id(),
                'path' => $path,
            ]);
        }
    }
}
