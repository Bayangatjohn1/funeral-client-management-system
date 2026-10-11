<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $user->load('branch');

        return view('profile.edit', compact('user'));
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->isSystemAdmin() && $request->input('email') !== $user->email) {
            $request->validate(['current_password' => ['required', 'current_password:web']]);
        }

        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'contact_number' => $user->contact_number,
            'position' => $user->position,
            'address' => $user->address,
        ];

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        AuditLogger::log(
            action: 'user.profile_updated',
            actionType: 'update',
            entityType: 'user',
            entityId: $user->id,
            metadata: [
                'before' => $before,
                'after' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'contact_number' => $user->contact_number,
                    'position' => $user->position,
                    'address' => $user->address,
                ],
            ],
            branchId: $user->branch_id
        );

        return Redirect::route('profile.edit')->with('success', 'Profile updated successfully.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        abort_if($request->user()->isSystemAdmin(), 403, 'System Admin accounts must be managed through User Management.');
        abort_if(\App\Models\User::where('privileged_for_user_id', $request->user()->id)->exists(), 403, 'This account owns a privileged account. Contact a System Admin.');
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
