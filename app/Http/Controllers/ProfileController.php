<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Support\PersonName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function security(Request $request)
    {
        return view('profile.security', [
            'user' => $request->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();
        $data = $validated;
        $data['name'] = PersonName::combine($validated['first_name'] ?? null, $validated['last_name'] ?? null);
        unset($data['first_name'], $data['last_name']);

        $user->update($data);

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Profile details updated successfully.');
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();
        $shouldLogoutOtherDevices = $request->boolean('logout_other_devices');

        if ($shouldLogoutOtherDevices) {
            Auth::logoutOtherDevices($validated['current_password']);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
        ])->save();

        return redirect()
            ->route('profile.security')
            ->with('status', $shouldLogoutOtherDevices
                ? 'Password updated. Other signed-in devices have been logged out.'
                : 'Password updated successfully.');
    }
}
