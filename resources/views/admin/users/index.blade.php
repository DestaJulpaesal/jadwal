<section class="surface-card overflow-hidden">
    <div class="flex flex-col gap-1 border-b border-line px-5 py-4 sm:px-6">
        <h2 class="font-semibold">Manajemen Akun</h2>
        <p class="text-sm text-muted">Cari akun, kelola akses, dan bantu pengguna memulihkan akun.</p>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="grid gap-3 border-b border-line p-4 sm:grid-cols-2 lg:grid-cols-4">
        <label class="sr-only" for="account-search">Cari nama atau email</label>
        <input id="account-search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Cari nama atau email" class="form-control">
        <label class="sr-only" for="account-status">Filter status akun</label>
        <select id="account-status" name="status" class="form-control">
            <option value="">Semua status</option>
            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
            <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
        </select>
        <label class="sr-only" for="account-role">Filter peran akun</label>
        <select id="account-role" name="role" class="form-control">
            <option value="">Semua peran</option>
            <option value="user" @selected(($filters['role'] ?? '') === 'user')>Pengguna</option>
            <option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Admin</option>
        </select>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Terapkan</button>
            <a href="{{ route('admin.users.index') }}" class="btn-secondary">Reset</a>
        </div>
    </form>

    @if ($users->isEmpty())
        <div class="p-8 text-center">
            <p class="font-medium">Akun tidak ditemukan.</p>
            <p class="mt-1 text-sm text-muted">Coba ubah kata pencarian atau filter yang dipilih.</p>
        </div>
    @else
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Daftar akun pengguna</caption>
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-muted">
                    <tr>
                        <th scope="col" class="px-5 py-3">Nama</th>
                        <th scope="col" class="px-5 py-3">Email</th>
                        <th scope="col" class="px-5 py-3">Peran</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3">Dibuat</th>
                        <th scope="col" class="px-5 py-3">Kelola</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($users as $user)
                        <tr>
                            <td class="px-5 py-4 font-medium">{{ $user->name }}</td>
                            <td class="px-5 py-4 text-muted">{{ $user->email }}</td>
                            <td class="px-5 py-4">{{ $user->isAdmin() ? 'Admin' : 'Pengguna' }}</td>
                            <td class="px-5 py-4"><x-status-badge :status="$user->is_active ? 'Aktif' : 'Nonaktif'" /></td>
                            <td class="whitespace-nowrap px-5 py-4 text-muted">{{ $user->created_at->translatedFormat('d M Y') }}</td>
                            <td class="px-5 py-4">
                                @if ($user->id !== auth()->id())
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('admin.users.show', $user) }}" class="btn-secondary min-h-11 px-3 py-1.5 text-xs">Detail</a>
                                        <x-confirm-action
                                            :action="route('admin.users.status', $user)"
                                            method="PATCH"
                                            :title="$user->is_active ? 'Nonaktifkan akun?' : 'Aktifkan akun?'"
                                            :message="$user->is_active ? 'Pengguna tidak akan dapat masuk selama akunnya nonaktif.' : 'Pengguna akan dapat masuk kembali setelah akun diaktifkan.'"
                                            :label="$user->is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                            tone="secondary"
                                            :icon="$user->is_active ? 'nonaktifkan' : 'selesai'"
                                        />
                                        <x-confirm-action
                                            :action="route('admin.users.destroy', $user)"
                                            method="DELETE"
                                            title="Hapus akun secara permanen?"
                                            message="Semua tugas dan kategori milik pengguna ini juga akan dihapus. Tindakan ini tidak dapat dibatalkan."
                                            label="Hapus akun"
                                        />
                                    </div>
                                @else
                                    <a href="{{ route('admin.users.show', $user) }}" class="btn-secondary min-h-11 px-3 py-1.5 text-xs">Detail akun</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <ul class="divide-y divide-line md:hidden">
            @foreach ($users as $user)
                <li class="space-y-3 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $user->name }}</p>
                            <p class="break-all text-sm text-muted">{{ $user->email }}</p>
                        </div>
                        <x-status-badge :status="$user->is_active ? 'Aktif' : 'Nonaktif'" class="shrink-0" />
                    </div>
                    <p class="text-xs text-muted">{{ $user->isAdmin() ? 'Admin' : 'Pengguna' }} · Dibuat {{ $user->created_at->translatedFormat('d M Y') }}</p>
                    @if ($user->id !== auth()->id())
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('admin.users.show', $user) }}" class="btn-secondary min-h-11 px-3 py-1.5 text-xs">Detail</a>
                            <x-confirm-action
                                :action="route('admin.users.status', $user)"
                                method="PATCH"
                                :title="$user->is_active ? 'Nonaktifkan akun?' : 'Aktifkan akun?'"
                                :message="$user->is_active ? 'Pengguna tidak akan dapat masuk selama akunnya nonaktif.' : 'Pengguna akan dapat masuk kembali setelah akun diaktifkan.'"
                                :label="$user->is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                tone="secondary"
                                :icon="$user->is_active ? 'nonaktifkan' : 'selesai'"
                            />
                            <x-confirm-action
                                :action="route('admin.users.destroy', $user)"
                                method="DELETE"
                                title="Hapus akun secara permanen?"
                                message="Semua tugas dan kategori milik pengguna ini juga akan dihapus. Tindakan ini tidak dapat dibatalkan."
                                label="Hapus akun"
                            />
                        </div>
                    @else
                        <a href="{{ route('admin.users.show', $user) }}" class="btn-secondary min-h-11 px-3 py-1.5 text-xs">Detail akun</a>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <div class="border-t border-line px-4 py-4 sm:px-6">{{ $users->links() }}</div>
</section>
