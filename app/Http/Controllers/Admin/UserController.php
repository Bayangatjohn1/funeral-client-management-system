<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('branch')->latest()->get();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $branches = Branch::orderBy('branch_name')->get();
        return view('admin.users.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,staff,owner',
            'branch_id' => 'nullable|exists:branches,id',
            'contact_number' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'can_encode_any_branch' => 'nullable|boolean',
        ]);

        if ($validated['role'] === 'staff' && empty($validated['branch_id'])) {
            return back()->withErrors(['branch_id' => 'Branch is required for staff accounts.'])->withInput();
        }

        $canEncodeAnyBranch = $validated['role'] === 'staff' ? $request->boolean('can_encode_any_branch') : false;

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'branch_id' => $validated['branch_id'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'position' => $validated['position'] ?? null,
            'address' => $validated['address'] ?? null,
            'can_encode_any_branch' => $canEncodeAnyBranch,
            'is_active' => true,
        ]);

        $returnTo = $request->input('return_to');
        if ($returnTo) {
            return redirect()->to($returnTo)->with('success', 'User created successfully.');
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $branches = Branch::orderBy('branch_name')->get();
        return view('admin.users.edit', compact('user', 'branches'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,staff,owner',
            'branch_id' => 'nullable|exists:branches,id',
            'contact_number' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'can_encode_any_branch' => 'nullable|boolean',
        ]);

        if ($validated['role'] === 'staff' && empty($validated['branch_id'])) {
            return back()->withErrors(['branch_id' => 'Branch is required for staff accounts.'])->withInput();
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'branch_id' => $validated['branch_id'] ?? null,
            'contact_number' => $validated['contact_number'] ?? null,
            'position' => $validated['position'] ?? null,
            'address' => $validated['address'] ?? null,
            'can_encode_any_branch' => $validated['role'] === 'staff'
                ? $request->boolean('can_encode_any_branch')
                : false,
        ]);

        $returnTo = $request->input('return_to');
        if ($returnTo) {
            return redirect()->to($returnTo)->with('success', 'User updated successfully.');
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function toggleActive(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', "You can't deactivate your own account.");
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return back()->with('success', 'User status updated.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $user->password = Hash::make($validated['password']);
        $user->save();

        return back()->with('success', 'Password reset successfully.');
    }
}
