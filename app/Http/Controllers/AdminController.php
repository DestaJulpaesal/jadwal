<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,inactive'],
            'role' => ['nullable', 'in:admin,user'],
        ]);
        $query = User::query()
            ->select(['id', 'name', 'email', 'role', 'is_active', 'created_at'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('is_active', $status === 'active'))
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role));
        $users = $query->orderBy('name')->paginate(15)->withQueryString();
        $accountTotals = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
        ];

        return view('admin.dashboard', compact('users', 'accountTotals', 'filters'));
    }

    public function show(User $user)
    {
        $user->loadCount(['tasks', 'habits', 'goals', 'wallets']);

        return view('admin.users.show', compact('user'));
    }

    public function toggleStatus(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors(['account' => __('ui.feedback.admin_cannot_disable_self')]);
        }

        if ($user->is_active && $user->isAdmin()) {
            $activeAdmins = User::where('role', 'admin')->where('is_active', true)->count();
            if ($activeAdmins <= 1) {
                return back()->withErrors(['account' => __('ui.feedback.last_active_admin_disable')]);
            }
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $message = $user->is_active ? __('ui.feedback.account_enabled') : __('ui.feedback.account_disabled');

        return Feedback::success(redirect()->route('admin.dashboard'), $message);
    }

    public function updateRole(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors(['account' => __('ui.feedback.admin_cannot_change_own_role')]);
        }

        if ($user->isAdmin() && $user->is_active) {
            $activeAdmins = User::where('role', 'admin')->where('is_active', true)->count();
            if ($activeAdmins <= 1) {
                return back()->withErrors(['account' => __('ui.feedback.last_active_admin_role')]);
            }
        }

        $user->role = $user->isAdmin() ? 'user' : 'admin';
        $user->save();

        $message = $user->isAdmin() ? __('ui.feedback.role_admin') : __('ui.feedback.role_user');

        return Feedback::success(redirect()->route('admin.users.show', $user), $message);
    }

    public function sendPasswordResetLink(User $user)
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return Feedback::success(back(), __('ui.feedback.password_reset_sent_admin'));
        }

        return back()->withErrors(['account' => __($status)]);
    }

    public function destroy(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors(['account' => __('ui.feedback.admin_cannot_delete_self')]);
        }

        if ($user->is_active && $user->isAdmin()) {
            $activeAdmins = User::where('role', 'admin')->where('is_active', true)->count();
            if ($activeAdmins <= 1) {
                return back()->withErrors(['account' => __('ui.feedback.last_active_admin_delete')]);
            }
        }

        $user->delete();

        return Feedback::success(redirect()->route('admin.dashboard'), __('ui.feedback.account_deleted'));
    }
}
