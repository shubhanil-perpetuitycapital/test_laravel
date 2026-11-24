<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Staff accounts (owner only). Staff can manage orders, products and
 * content; only owners can change settings, coupons, delivery and staff.
 */
class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', ['users' => User::query()->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(['role' => User::ROLE_STAFF, 'is_active' => true])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        User::query()->create($request->validated() + ['email_verified_at' => now()]);

        return redirect()->route('admin.users.index')->with('success', 'Account created. Share the password with them privately.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($user->is($request->user()) && (! $data['is_active'] || $data['role'] !== User::ROLE_ADMIN)) {
            return back()->with('error', 'You cannot deactivate your own account or remove your own owner access.');
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Account updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->isOwner() && User::query()->where('role', User::ROLE_ADMIN)->count() <= 1) {
            return back()->with('error', 'The store needs at least one owner account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Account deleted.');
    }
}
