<?php

namespace App\Http\Controllers;

use App\Models\User;

class AdminController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'is_active', 'created_at'])
            ->orderBy('name')
            ->paginate(15);

        return view('admin.dashboard', compact('users'));
    }

    public function toggleStatus(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors(['account' => 'Admin tidak dapat menonaktifkan akunnya sendiri.']);
        }

        if ($user->is_active && $user->isAdmin()) {
            $activeAdmins = User::where('role', 'admin')->where('is_active', true)->count();
            if ($activeAdmins <= 1) {
                return back()->withErrors(['account' => 'Admin aktif terakhir tidak dapat dinonaktifkan.']);
            }
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $message = $user->is_active ? 'Akun berhasil diaktifkan.' : 'Akun berhasil dinonaktifkan.';

        return redirect()->route('admin.dashboard')->with('success', $message);
    }

    public function destroy(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors(['account' => 'Admin tidak dapat menghapus akunnya sendiri.']);
        }

        if ($user->is_active && $user->isAdmin()) {
            $activeAdmins = User::where('role', 'admin')->where('is_active', true)->count();
            if ($activeAdmins <= 1) {
                return back()->withErrors(['account' => 'Admin aktif terakhir tidak dapat dihapus.']);
            }
        }

        $user->delete();

        return redirect()->route('admin.dashboard')->with('success', 'Akun dan data jadwal miliknya berhasil dihapus.');
    }
}
